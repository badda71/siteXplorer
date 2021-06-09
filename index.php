<?php
/*
===============
= siteXplorer =
===============
copyright 2008 by Sebastian Weber <webersebastian@yahoo.de>
This software is licensed under the GNU general public license http://www.gnu.org/copyleft/gpl.html
http://sitexplorer.badda.de

*/
	// autoconfig
	define( '_VALID_SXR', 1 );
	include "cfg-dist.php";
	$defcfg = $cfg;
	$defkey = $key;
	if (file_exists("cfg.php")) {
		include "cfg.php";
	} else {
		if (array_key_exists('action', $_REQUEST) && $_REQUEST['action']!="saveprefs") $firststart=true;
	}
	include "lib.php";
	include "authn.php";

//echo "<pre>";print_r($cfg);echo "</pre>";
	if (array_key_exists('action', $_REQUEST) && $_REQUEST['action']=="saveprefs" && sxr_chkdemo("Modify Preferences")) {
		if (!$_REQUEST['cfg']['user']) $_REQUEST['cfg']['pass']=$cfg['pass']=""; // clear password if uname is empty
		if ($_REQUEST['cfg']['pass']) { // set new password
			setcookie("login_password", $_REQUEST['cfg']['pass']);
			$_REQUEST['cfg']['pass']=md5($_REQUEST['cfg']['pass']);
		} else $_REQUEST['cfg']['pass']=$cfg['pass'];
		$f=fopen("cfg.php","w");
		fwrite($f,"<?php\n");
		foreach ($_REQUEST['cfg'] as $k => $v) {
			if (!array_key_exists($k, $defcfg) || $defcfg[$k] != $v)
				fwrite($f,"\$cfg[\"$k\"]=\"$v\";\n");
		}
		foreach ($_REQUEST['key'] as $k => $v) {
			if (!array_key_exists($k, $defkey) || $defkey[$k] != $v)
				fwrite($f,"\$key[\"$k\"]=\"$v\";\n");
		}
		fwrite($f,"?".">\n");
		fclose($f);
		$_REQUEST['action']=="";

		// reload config
		unset ($cfg,$key);
		include "cfg-dist.php";
		include "cfg.php";
		setcookie("login_username", $cfg['user']);
	}
	if (array_key_exists('action', $_REQUEST) && $_REQUEST['action']=="clearcache") {
		if (sxr_chkdemo("Clear Cache")) {
			sxr_delete("thumbs");
			echo "OK";
		} else {
			echo $errmsg;
		}
		exit;
	}
	# functions
	function sxr_chkdemo($what) {
		global $demo_mode,$errmsg;
		if ($demo_mode) $errmsg="$what: Action prohibited in demo mode";
		return !$demo_mode;
	}
	
	function sxr_myerror($errno, $errstr, $errfile, $errline) {
		global $lasterr;
		switch ($errno) {
		case E_ERROR:
			echo "ERROR in line $errline of file $errfile: [$errno] $errstr\n";
			exit;
		case E_WARNING:
		//	echo "WARNING in line $errline of file $errfile: [$errno] $errstr<br>\n";
			$lasterr=$errstr;
		default:
		//	echo "NOTICE in line $errline of file $errfile: [$errno] $errstr<br>\n";
		}
	}	
	function sxr_delete($file) {
		if (file_exists($file)) {
			chmod($file,0777);
			if (is_dir($file)) {
				$handle = opendir($file);
				if (!$handle) return "Cannot open folder $file: $lasterr";
				while($filename = readdir($handle)) {
					if ($filename != "." && $filename != "..") {
						if ($msg=sxr_delete($file."/".$filename)) return $msg;
					}
				}
				closedir($handle);
				if (!rmdir($file)) return "Cannot delete folder $file: $lasterr";
			} else {
				if (!unlink($file)) return "Cannot delete file $file: $lasterr";
			}
		}
		return "";
	}
	function sxr_tnurl($f) {
		global $cfg,$cwd;
		$tp = "$cwd/thumbs$f";
		if ($cfg["enablecache"] && file_exists($tp) && filemtime($tp)==filemtime("$cfg[root_path]/$f"))
			return "thumbs".urlenc($f);
		return "tn.php?p=".urlenc($f);
	}
	function sxr_cleancache() { // clean cache all 10 minutes
		global $cfg,$curdir,$cwd;
		if (!file_exists("$cwd/thumbs$curdir")) return;
		if (!file_exists("$cwd/thumbs$curdir/.cacheage")) $fp=fopen("$cwd/thumbs$curdir/.cacheage", "w+");
		else $fp = fopen("$cwd/thumbs$curdir/.cacheage", "r+");
		flock($fp, LOCK_SH);
		$t=fread($fp,256);
		if ($t && $t+600>time()) {fclose($fp);return;}
		flock($fp, LOCK_EX);fseek($fp,0);fwrite($fp,time());fclose($fp);
		$fp = opendir("$cwd/thumbs$curdir");
		while(false !== ($file = readdir($fp))){
			if ($file==".." || $file=="." || $file==".cacheage" ||
				(is_dir("$cwd/thumbs$curdir/$file") && is_dir($file)) ||
				(file_exists($file) && filemtime($file)==filemtime("$cwd/thumbs$curdir/$file"))) continue;
			sxr_delete("$cwd/thumbs$curdir/$file");
		}
	}

	function sxr_xcopy($source, $dest, $move) {
		if (!file_exists($source)) return "";
		if ($source==$dest)	{
			if ($move) return "";
			$di=pathinfo($dest);$i=0;
			while (file_exists($dest))
				$dest=($di['dirname']?"$di[dirname]/":"")."Copy ".(++$i>1?"($i) ":"")."of $di[basename]";
		}
		if ($move) {
			if (file_exists($dest)) unlink($dest);
			if (rename($source,$dest)) return "";
			else return "Cannot move $source to $dest: $lasterr";}
		if (!is_dir($source)) {
			if (copy($source,$dest) || filesize($source)==0) return "";
			else return "Cannot copy $source to $dest: $lasterr";}
		if (substr($dest,0,strlen($source)+1)==$source."/") return "Cannot copy $source: The destination folder is the same as the source folder";
		if (!file_exists($dest) && !mkdir($dest)) return "Cannot create folder $dest: $lasterr";
		if (!($d = opendir($source))) return "Cannot open folder $source: $lasterr";
		while(false !== ($f = readdir($d))){
			if ($f=="." || $f=="..") continue;
			if($msg=sxr_xcopy("$source/$f","$dest/$f",$move)) return $msg;
		}
		closedir($d);
		return "";
	}
	function sxr_execprog($s) {
		$a=array();
		exec ($s." 2>&1",$a,$i);
		$out=join("\n",$a);
		$trans = get_html_translation_table(HTML_ENTITIES);
		$trans["\n"]="<br>";
		$trans["\r"]="";
		$out = strtr($out, $trans);
		return array($i,$out);
	}
	function sxr_zip($zipfile,$files) {
		global $cfg,$zipcom,$curdir,$errmsg;
		if ($cfg["zmethod"]==2) {
			$f=""; foreach($files as $fi) $f.=escapeshellarg($fi)." ";
			$c=preg_replace("/@FILES@/",$f,preg_replace("/@ZIPFILE@/",escapeshellarg($zipfile),$zipcom));
			$a=sxr_execprog($c);
			if ($a[0]) $errmsg.="Zipping files failed:\n$cfg[root_path]$curdir> $c\n$a[1]\n$cfg[root_path]$curdir><br>";
		} elseif ($cfg["zmethod"]==1) {
			require_once('pclzip.lib.php');
			$z = new PclZip($zipfile);
			if ($z->create($files)==0) $errmsg="Zipping files failed: ".$z->errorInfo(true);
		} else $errmsg.="Zipping files failed: no method to zip files!<br>";
		if ($errmsg) return false;
		return true;
	}

	function sxr_unzip($src_file) {
		global $unzipcom,$cfg,$curdir;
		if ($cfg["uzmethod"]==2) {
			$c=preg_replace("/@ZIPFILE@/",escapeshellarg($src_file),$unzipcom);
			$a=sxr_execprog($c);
			return $a[0]?"Unzip failed:<br>$cfg[root_path]$curdir> $c<br>$a[1]<br>$cfg[root_path]$curdir>":"";
		} elseif ($cfg["uzmethod"]==1) {
			require_once('pclzip.lib.php');
			$z = new PclZip($src_file);
			return $z->extract()==0?"Unzipping files failed: ".$z->errorInfo(true):"";
		} else return "Unzipping files failed: no method to unzip files!";
	}

	function sxr_format_fsize($i) {
		$x=0;
		$a=array("bytes","KB","MB","GB","TB","PB");
		$i=round($i);
		while ($i>1000) {$i/=1024;$x++;}
		$i=round($i,min(2,2-floor(log($i,10))));
		return "$i $a[$x]";	
	}
	function sxr_parseperms($p) {
		if (($p & 0xC000) == 0xC000) $i = 's';		// Socket
		elseif (($p & 0xA000) == 0xA000) $i = 'l';	// Symbolic Link
		elseif (($p & 0x8000) == 0x8000) $i = '-';	// Regular
		elseif (($p & 0x6000) == 0x6000) $i = 'b';	// Block special
		elseif (($p & 0x4000) == 0x4000) $i = 'd';	// Directory
		elseif (($p & 0x2000) == 0x2000) $i = 'c';	// Character special
		elseif (($p & 0x1000) == 0x1000) $i = 'p';	// FIFO pipe
		else $i = 'u';	// Unknown
		// Owner
		$i .= (($p & 0x0100) ? 'r' : '-');
		$i .= (($p & 0x0080) ? 'w' : '-');
		$i .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x' ) : (($p & 0x0800) ? 'S' : '-'));
		// Group
		$i .= (($p & 0x0020) ? 'r' : '-');
		$i .= (($p & 0x0010) ? 'w' : '-');
		$i .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x' ) : (($p & 0x0400) ? 'S' : '-'));
		// World
		$i .= (($p & 0x0004) ? 'r' : '-');
		$i .= (($p & 0x0002) ? 'w' : '-');
		$i .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x' ) : (($p & 0x0200) ? 'T' : '-'));
		return $i;
	}
	
	#####################################
	# Start of script
	#
	session_start();	// need a session to store the clipboard - cookies are not large enough
	set_error_handler ( "sxr_myerror" );

	// set my global variables
	$view=($_POST['view']?$_POST['view']:($_GET['view']?$_GET['view']:($_COOKIE['view']?$_COOKIE['view']:"d")));
	$curdir=shortenpath(isset($_POST['curdir'])?$_POST['curdir']:(isset($_GET['curdir'])?$_GET['curdir']:(isset($_COOKIE['curdir'])?$_COOKIE['curdir']:"")));
	$order=($_POST['order']?$_POST['order']:($_GET['order']?$_GET['order']:($_COOKIE['order']?$_COOKIE['order']:"nasata")));
	$action=$_REQUEST['action'];
	$arg=$_REQUEST['arg'];
	$curpos=$_REQUEST['curpos'];
	$curitem=$_REQUEST['curitem'];
	$cwd=getcwd();
	if ($cfg["root_path"]=="/" && $curdir) $cfg["root_path"]="";

	// store curdir, view & order in a cookie
	if ($view!=$_COOKIE["view"]) setcookie("view",$view,time()+51840000);
	if ($order!=$_COOKIE["order"]) setcookie("order",$order,time()+51840000);
	if ($curdir!=$_COOKIE["curdir"]) setcookie("curdir",$curdir,time()+51840000);

	chdir($cfg["root_path"]);
	if (!chdir(".".$curdir)) $curdir="";
	if (!isset($_SESSION['cb_action'])) $_SESSION['cb_action'] = 0;
	if ($_REQUEST['cb_action']) {
		$_SESSION['cb_action'] = $_REQUEST['cb_action'];
		$_SESSION['cb_path'] = $_REQUEST['cb_path'];
		$_SESSION['cb_files'] = $_REQUEST['cb_files'];
	}
	$cb_action=$_SESSION['cb_action'];
	$cb_path=$_SESSION['cb_path'];
	$cb_files=$_SESSION['cb_files'];

	if ($cfg["enablecache"]) sxr_cleancache();

	$a=array();
	$m="'^(".join("|",$image_ext[$cfg["tnmethod"]]).")$'";
	foreach ($mimes as $k => $v) if (preg_match($m,$v)) $a[]=$k;
	$img_can_rotate='\.'.join('$|\.',$a).'$';	// image formats: imglib_can_read() AND imglib_can_write()

	foreach ($browserimg as $k) $a=array_merge($a,array_keys($mimes,$k));
	$img_can_show='\.'.join('$|\.',array_unique($a)).'$';	// image formats: imglib_can_read() OR browser_can_read()

	###########
	# ACTIONS
	if ($action=="mkdir" && $arg && sxr_chkdemo("New Folder")) {
		if (!mkdir($arg)) $errmsg="Cannot create folder $arg: $lasterr";
		else $curitem=$arg;
	}
	if ($action=="delete" && $arg && sxr_chkdemo("Delete Files")) foreach (explode(':',$arg) as $what) if (!preg_match("/^\\.\\.?$/",$what) && ($errmsg=sxr_delete($what))) break;
	if ($action=="command" && $arg && sxr_chkdemo("Execute command")) {
		$a=sxr_execprog($arg);
		if ($a[0]) $errmsg="<b>Command could not be executed:</b><br>$cfg[root_path]$curdir> $arg<br>$a[1]<br>$cfg[root_path]$curdir>";
		else $msg="$cfg[root_path]$curdir> $arg<br>$a[1]<br>$cfg[root_path]$curdir>";
	}
	if ($action=="paste" && $_SESSION['cb_files'] && sxr_chkdemo("Paste Files")) {
		foreach (explode(':',$_SESSION['cb_files']) as $f)
			if ($errmsg=sxr_xcopy("$cfg[root_path]$_SESSION[cb_path]/$f","$cfg[root_path]$curdir/$f",$cb_action==2?1:0)) break;
		if ($cb_action==2) {$cb_action=0;$cb_path=$cb_files="";}
	}
	if ($action=="rename" && sxr_chkdemo("Rename File")) {
		$a=explode(":",$arg);
		if (!rename($a[0],$a[1])) $errmsg="Cannot rename $a[0]: $lasterr";
		$curitem=$a[1];
	}
	if ($action=="upload" && sxr_chkdemo("Upload Files")) {
		$count=1;$errmsg="";
		foreach ($_FILES as $n => $f) {
			if (!$f['name']) continue;
			$n=preg_replace("/^file/","",$n);
			if (preg_match("/\\.zip$/",$f['name']) && $_REQUEST["uz$n"]) $errmsg=sxr_unzip($f['tmp_name']);
			else {	// copy
				if (file_exists($f['name']) && $_REQUEST["ow"] && !unlink($f['name'])) $errmsg.="Cannot delete $f[name]: $lasterr<br>";
				else if (!rename($f['tmp_name'],$f['name'])) $errmsg.="Cannot store $f[name]: $lasterr<br>";
			}
		}	
	}
	if ($action=="download" && $arg) {
		$b=explode(":",$arg);
		// zip content
		if (!($zipfile=tempnam("/tmp","SX"))) $zipfile=($_ENV['TMP']?$_ENV['TMP']:($_ENV['TEMP']?$_ENV['TEMP']:$cfg["root_path"]))."/__".md5(time().$_SERVER["REQUEST_URI"]).".zip";
		unlink($zipfile);
		if (sxr_zip($zipfile,$b)) {
			// calculate zip filename
			if (count($b)==1) $fn=preg_replace("/\\.[^\\.]*\$/","",$b[0]);
			else $fn=$curdir?preg_replace('|^.*/([^/]+)$|',"$1",$curdir):"root";
			header("Content-type: application/zip");
			header("Content-disposition: attachment; filename=\"$fn.zip\"");
			readfile($zipfile);
			unlink($zipfile);
			exit;
		}
	}
	if ($action=="chmod" && $arg && sxr_chkdemo("Change File Permissions")) {
		$b=explode(":",$arg);
		$m=octdec("0".array_shift($b));
		foreach($b as $f) if (!preg_match("/^\\.\\.?$/",$f) && !chmod($f,$m)) $errmsg.="Cannot chmod $f: $lasterr<br>";
		$curitem=$b[0];
	}
	if ($action=="extract" && $arg && sxr_chkdemo("Extract Archive")) $errmsg=sxr_unzip("$cfg[root_path]$curdir/$arg");
	if (preg_match("/^rotate(.*)\$/",$action,$m) && $arg && sxr_chkdemo("Rotate Image")) {
		$mi=file2mime($arg);
		if ($cfg["jpegrot"]==2 && $mi=="image/jpeg") {
			sxr_execprog("jpegtran -rotate ".($m[1]?90:270)." \"$arg\" >\"$arg.__rot__\"");
			unlink($arg);
			rename("$arg.__rot__",$arg);
		} elseif (!preg_match("'^(".join("|",$image_ext[$cfg["tnmethod"]]).")$'",$mi)) {$errmsg.="Image type $mi not supported by image processing library<br>";
		} else {
			if ($cfg["tnmethod"]==1) {
				sxr_execprog("mogrify -rotate ".($m[1]?90:270)." \"$arg\"");}
			elseif ($cfg["tnmethod"]==2) {
				sxr_execprog($pbm_commands[$mi][0]." \"$arg\" | pnmrotate ".($m[1]?90:270)." | ".$pbm_commands[$mi][1]." >\"$arg.__rot__\"");
				unlink($arg);
				rename("$arg.__rot__",$arg);
			}
			elseif ($cfg["tnmethod"]==3) {
				if (!$img=imagecreatefromstring(file_get_contents("$cfg[root_path]$curdir/$arg"))) $errmsg.="Cannot read image<br>";
				else {
					$img=imagerotate($img, $m[1]?270:90, 0);
					$ext=preg_replace('|^.*[/\.]|',"",$mi);
					eval("image$ext(\$img,\"$arg\");");
				}
			}
		}
		$curitem=$arg;
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><title><?php echo $curdir?$curdir:"/" ?> - SiteXplorer</title>
<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
<link type="text/css" rel="StyleSheet" href="style.css.php">
<script type="text/javascript">
	// some global variables which are configurable
	var curdir="<?php echo preg_replace("/\"/","\\\"",$curdir);?>";
	var cb_path="<?php echo $cb_path?>";// clipboard dir
	var cb_files="<?php echo $cb_files?>";// clipboard files
	var cb_action=<?php echo $cb_action?>;// clipboard action (2=cut,1=copy)
	var view="<?php echo $view?>"; // whats out current view? d=details, i=icons, t=thumnails, s=filmstrip
	var imgCanShowRe=/<?php echo $img_can_show;?>/i; // regular expression to check files if they can be shown
	var imgCanRotateRe=/<?php echo $img_can_rotate;?>/i; // regular expression to check files if they can be shown
	var curdirenc="<?php echo urlenc(shortenpath($curdir))?>"; // escaped and shortened current path
	var cpos=<?php echo $curpos>0?$curpos+0:1;?>; // predefined cursor position - numeric
	var curitem="<?php echo $curitem?>"; // predefined cursor position - name (name overrides number)
	var firststart=<?php echo $firststart?1:0?>; // are we starting up for the first time?
	var order="<?php echo $order?>"; // what are we sorting for?
	// whats the name for each keycode? code -> name
	var ks=new Array();<?php foreach ($ks as $k => $v) echo "ks[$v]=\"$k\";";?>

	// my configured keys
	var k_action=new Array();k_code=new Array();k_ctrl=new Array();k_alt=new Array();k_shift=new Array();
<?php 
	$i=0;
	foreach ($key as $ac => $k) {
		if (preg_match('/^(Ctrl-)?(Alt-)?(Shift-)?(.+)$/',$k,$m)) {
			echo "\tk_action[$i]=\"$ac\";".
				"k_code[$i]=".$ks[$m[4]].";".
				"k_ctrl[$i]=".($m[1]?"true":"false").";".
				"k_alt[$i]=".($m[2]?"true":"false").";".
				"k_shift[$i]=".($m[3]?"true":"false").";\n";
			$i++;
		}
	}
	?>
</script>
<script type="text/javascript" src="javascript.js"></script>
</head>
<body id=body>
<!-- Popup Windows and utility layers -->
<div id=back></div>
<table id=f_zoom border=0 cellpadding=0 cellspacing=0><tr><td><img src=img/win_tl.gif></td><td style='background:#BDBDBD url(img/img_close.gif) no-repeat scroll top right;color:#4F4F4F;cursor:pointer' onclick="toggle_zoom()" align=left><img src=img/img_zoom16.gif style='margin-right:6px' align=left><div id=zoomtitle>SiteXplorer - Zoom</div></td><td><img src=img/win_tr.gif></td></tr><tr><td bgcolor=#BDBDBD></td><td style='border:1px solid white' id=zoomtd align=center valign=middle onclick="toggle_zoom()"><div id=zoomdiv>
<img id=zoomld src='img/spinner.gif' alt='Loading image'><img id=zoomimg src='' alt='No preview available'><div id=zoomno>No preview available.</div></div>
</td><td bgcolor=#BDBDBD></td></tr><tr><td><img src=img/win_bl.gif></td><td bgcolor=#BDBDBD></td><td><img src=img/win_br.gif></td></tr></table>

<table id=f_about border=0 cellpadding=0 cellspacing=0><tr><td><img src=img/win_tl.gif></td><td style='background:#BDBDBD url(img/img_close.gif) no-repeat scroll top right;color:#4F4F4F;cursor:pointer' onclick="ca_about()" align=left><img src=img/img_logo16.gif style='margin-right:6px' align=absmiddle>SiteXplorer - About</td><td><img src=img/win_tr.gif></td></tr><tr><td bgcolor=#BDBDBD></td><td style='border:1px solid white'>
<div id=about><img onClick="ca_about()" src=img/img_logo.gif>
<div>siteXplorer v1.1</div>
&copy;2008 by Sebastian Weber &lt;<a href="mailto:websersebastian@yahoo.de">webersebastian@yahoo.de</a>><br>
This software is licensed under the <a href="http://www.gnu.org/copyleft/gpl.html" target="_new">GNU general public license</a><br>
Website <a target="_new" href="http://sitexplorer.badda.de">sitexplorer.badda.de</a></div>
</td><td bgcolor=#BDBDBD></td></tr><tr><td><img src=img/win_bl.gif></td><td bgcolor=#BDBDBD></td><td><img src=img/win_br.gif></td></tr></table>

<table id=f_prefs border=0 cellpadding=0 cellspacing=0><tr><td><img src=img/win_tl.gif></td><td style='background:#BDBDBD url(img/img_close.gif) no-repeat scroll top right;color:#4F4F4F;cursor:pointer' onclick="ca_prefs()" align=left><img src=img/img_prefs16.gif style='margin-right:6px' align=absmiddle>SiteXplorer - Preferences</td><td><img src=img/win_tr.gif></td></tr><tr><td bgcolor=#BDBDBD></td><td style='border:1px solid white'>
<div id=prefs><table border=0 cellpadding=0 cellspacing=0 width="100%" height="100%"><tr><td valign=middle align=center><form><img src="img/spinner.gif"><br><br><input type="button" value="Cancel" onClick="ca_prefs()"></form></td></tr></table></div>
</td><td bgcolor=#BDBDBD></td></tr><tr><td><img src=img/win_bl.gif></td><td bgcolor=#BDBDBD></td><td><img src=img/win_br.gif></td></tr></table>	

<form name=u method=post enctype="multipart/form-data" action=index.php>
<table id=f_upload border=0 cellpadding=0 cellspacing=0><tr><td><img src=img/win_tl.gif></td><td style='background:#BDBDBD url(img/img_close.gif) no-repeat scroll top right;color:#4F4F4F;cursor:pointer' onclick="ca_upload()" align=left><img src=img/img_upload16.gif style='margin-right:6px' align=absmiddle>SiteXplorer - Upload Files</td><td><img src=img/win_tr.gif></td></tr><tr><td bgcolor=#BDBDBD></td><td style='border:1px solid white'>
<div id=upload>
<div id=upspin><table border=0 cellpadding=0 cellspacing=0 width="100%" height="100%"><tr><td valign=middle align=center><img src=img/spinner.gif align=middle></td></tr></table></div>
<input type=hidden name=curdir value="<?php echo htmlspecialchars($curdir);?>">
<input type=hidden name=order value="<?php echo $order;?>">
<input type=hidden name=action value=upload>
<input type=hidden name=view value="<?php echo $view ?>">
<a href="#" onmouseup="addUpload()"><img src="img/img_addfile.gif"> add file ...</a><br>
<div id=ulfiles><div class=ulfile><input type=file name=file1 size=50 onchange="this.nextSibling.style.display=this.value.match(/\.zip$/)?'inline':'none'"><span><input type=checkbox name=uz1 value=1>Unzip File</span></div></div>
<input type=checkbox name=ow value=1> Overwrite existing files<br> 
<div align=right><input type=button class=but value=Upload onmouseup="do_upload()"> <input class=but type=button value=Cancel onmouseup="ca_upload()"></div>
</div>
</td><td bgcolor=#BDBDBD></td></tr><tr><td><img src=img/win_bl.gif></td><td bgcolor=#BDBDBD></td><td><img src=img/win_br.gif></td></tr></table>	
</form>

<form method=get name=f action=index.php>
<input type=hidden name=curpos value="">
<input type=hidden name=curdir value="<?php echo htmlspecialchars($curdir);?>">
<input type=hidden name=order value="<?php echo $order;?>">
<input type=hidden name=action value="">
<input name=arg value="" style="display:none;position:absolute;z-index:999" onblur="ca_rename()">
<input type=hidden name=cb_action value="">
<input type=hidden name=cb_path value="">
<input type=hidden name=cb_files value="">
<!-- Toolbar -->
<table id=head>
<?php
	if ($errmsg) echo ("<tr><td colspan=3 align=center><div class=warning>$errmsg</div></td></tr>");
	if ($msg) echo ("<tr><td colspan=3 align=center><div class=notice>$msg</div></td></tr>");?>
<tr><td colspan=2 class=toolbar><table width=100% cellpadding=0 cellspacing=0 border=0><tr><td><img class=tbbut
src="img/tool_dirup<?php echo $curdir?"":"_bw"?>.gif" title="Up<?php echo $key["dirup"]?" ($key[dirup])":"";?>"<?php if ($curdir) 
	echo " onclick=\"goTo(&quot;".htmlspecialchars(shortenpath($curdir."/.."))."&quot;)\""?>><img
src="img/tool_sep.gif"><img class=tbbut
src="img/tool_upload.gif" title="Upload Files<?php echo $key["upload"]?" ($key[upload])":"";?>" id="icul"
	onclick="ac_upload()"><img class=tbbut
src="img/tool_download_bw.gif" title="Download Files as ZIP<?php echo $key["download"]?" ($key[download])":"";?>" id="icdl"
	onclick="ac_download()"><img class=tbbut
src="img/tool_newfolder.gif" title="New Folder<?php echo $key["mkdir"]?" ($key[mkdir])":"";?>"
	onclick="ac_mkdir()"><img class=tbbut
src="img/tool_extract_bw.gif" title="Extract Zipfile<?php echo $key["extract"]?" ($key[extract])":"";?>" id="icext"
	onclick="ac_extract()"><img
src="img/tool_sep.gif"><img class=tbbut
src="img/tool_selectall.gif" title="Select All<?php echo $key["selall"]?" ($key[selall])":"";?>" id="icsa"
	onclick="ac_sall()"><img class=tbbut
src="img/tool_invsel.gif" title="Invert Selection<?php echo $key["dselall"]?" ($key[dselall])":"";?>"
	onclick="ac_dsall()"><img class=tbbut
src="img/tool_rename.gif" title="Rename<?php echo $key["rename"]?" ($key[rename])":"";?>" id="icren"
	onclick="ac_rename()"><img class=tbbut
src="img/tool_chmod_bw.gif" title="Change Permissions<?php echo $key["perms"]?" ($key[perms])":"";?>" id="icchm"
	onclick="ac_chmod()"><img class=tbbut
src="img/tool_delete_bw.gif" title="Delete<?php echo $key["del"]?" ($key[del])":"";?>" id="icdel"
	onclick="ac_delete()"><img class=tbbut
src="img/tool_cut_bw.gif"  id="iccut" title="Cut<?php echo $key["cut"]?" ($key[cut])":"";?>"
	onclick="ac_copy(1)"><img class=tbbut
src="img/tool_copy_bw.gif"  id="iccpy" title="Copy<?php echo $key["copy"]?" ($key[copy])":"";?>"
	onclick="ac_copy(0)"><img class=tbbut
src="img/tool_paste<?php echo $_REQUEST['cb_files']?"":"_bw"?>.gif"  id="icpst" title="Paste<?php echo $key["paste"]?" ($key[paste])":"";?>"
	onclick="ac_paste()"><img
src="img/tool_sep.gif"><img
src="img/tool_view.gif" title="Views"><select name=view onChange="document.f.curpos.value=window.curpos;document.f.action.value='';subfrm()" title="Views">
<option value="s"<?php echo $view=="s"?" selected":"" ?>>Filmstrip (<?php echo $key["vfilm"]?>)</option>
<option value="t"<?php echo $view=="t"?" selected":"" ?>>Thumbnails (<?php echo $key["vthumb"]?>)</option>
<option value="i"<?php echo $view=="i"?" selected":"" ?>>Icons (<?php echo $key["vicon"]?>)</option>
<option value="d"<?php echo $view=="d"?" selected":"" ?>>Details (<?php echo $key["vdet"]?>)</option>
</select> <img class=tbbut
src="img/tool_run.gif" title="Run Command<?php echo $key["command"]?" ($key[command])":"";?>"
	onclick="ac_command()"> <input type="text" name="command" style="color:#AAAAAA" value="type command here"
	onfocus="if (this.value=='type command here') {this.value='';this.style.color='#000000';}"
	onblur="if (this.value=='') {this.style.color='#AAAAAA';this.value='type command here';}"><img
src="img/tool_sep.gif"><img class=tbbut
src="img/tool_prefs.gif"  id="icpre" title="Preferences<?php echo $key["prefs"]?" ($key[prefs])":"";?>"
	onclick="ac_prefs()">
</td><td><img class=tbbut src="img/tool_logout.gif" id="lclog" title="Logout<?php echo $key["logout"]?" ($key[logout])":"";?>" onclick="ac_logout()"></td></tr></table></td>
<td rowspan=2 valign=top id=logo><img src="img/img_logo.gif" alt="SiteXplorer - About" onClick="ac_about()"></td></tr>
<!-- Address Bar -->

<tr><td class=a1>Address</td><td class=a2><div class=a2>
<?php
	echo "<img class=icon16 src=\"img/icon_".$folder_icon."16.gif\"> <a id=a href='#' onmouseup=\"goTo('')\">root</a>";
	$p="";
	foreach (explode('/',$curdir) as $i) {
		if ($i=="") continue;
		$p.="/$i";
		echo " / <a href='#' onmouseup=\"goTo(&quot;".htmlspecialchars($p)."&quot;)\">$i</a>";
	}
?></div>
</td></tr></table>
<!-- File listing -->
<table id="main"><tr><td>
<?php
	$files=array();
	$fd=$fn=$ft=$fs=$fm=array();
	$count=0;
	$tsize=0;
	$dir=array();
	if($handle = opendir(".")) while(false !== ($file = readdir($handle))) $dir[]=$file;
	foreach ($dir as $file) {
		if (is_dir($file)) {
			if ($file==".") continue;
			if ($file==".." && $curdir=="" && count($dir)>2) continue;
			if ($cfg["showhidden"]==0 && preg_match('/^\.+[^\.]/',$file)) continue;
			$fd[]=$files[$count]['d']=1;
			$ft[]=$files[$count]['t']="File Folder";
			$files[$count]['i']=$folder_icon;
			$fs[]='';
			//$fs[]=$files[$count]['s']='';
		} else {
			$fd[]=$files[$count]['d']=0;
			$m=$files[$count]['mi']=file2mime($file);
			foreach ($icons as $k => $v) if (preg_match("'^".$k."$'i",$m)) break;
			if ($v[1]) $ft[]=($files[$count]['t']=$v[1])."_".strtoupper(preg_replace("/^.*\./","",$file));
			else $ft[]=$files[$count]['t']=strstr($file,".")===false?"File":(strtoupper(preg_replace("/^.*\./","",$file))." File");
			$files[$count]['i']=$v[0];
			$fs[]=$files[$count]['s']=filesize($file);
			$tsize+=$files[$count]['s'];
		}
		$fa[]=$files[$count]['p']=fileperms($file);
		$fn[]=strtolower($file);$files[$count]['n']=$file;
		$fm[]=$files[$count]['m']=$file==".."?"":date("Y-m-d H:i",filemtime($file));
		$count++;
	}
	closedir($handle);
	# sort
	$s1=substr($order,0,1);$s2=substr($order,2,1);$s3=substr($order,4,1);
	array_multisort(
		$fd,
		substr($order,1,1)=='a'?SORT_DESC:SORT_ASC,
		$s1=='n'?$fn:($s1=='s'?$fs:($s1=='t'?$ft:$fm)),
		substr($order,1,1)=='a'?SORT_ASC:SORT_DESC,
		$s2=='n'?$fn:($s2=='s'?$fs:($s2=='t'?$ft:$fm)),
		substr($order,3,1)=='a'?SORT_ASC:SORT_DESC,
		$s3=='n'?$fn:($s3=='s'?$fs:($s3=='t'?$ft:$fm)),
		substr($order,5,1)=='a'?SORT_ASC:SORT_DESC,
		$files);

	$cutfiles=($cb_action==2 && $cb_path==$curdir && $cb_files)?explode(':',$cb_files):array();
	if ($view=="d")	printdetail($files);
	elseif ($view=="s")	printfilm($files);
	elseif ($view=="t")	printthumb($files);
	elseif ($view=="i")	printicon($files);

	function printfilm($f) {
		global $curdir,$cfg,$cutfiles,$image_ext,$key;
		
		echo "<table id=film><tr><td id=filmprevtd valign=middle align=center onClick='toggle_zoom()'><div id=fpdiv><img id=filmld src='img/spinner.gif' alt='Loading image'><img id=filmimg src='' alt='No preview available'><div id=filmno>No preview available.</div></div></td></tr>";
		echo "<tr><td id=filmnav><img class=fibut src='img/tool_prev.gif' title='Previous Image (Left Arrow)' onclick='doSelection(curpos-1<1?items.length:curpos-1,0,0,0);update_tb();'><img class=fibut src='img/tool_next.gif' title='Next Image (Right Arrow)' onclick='doSelection(curpos+1>items.length?1:curpos+1,0,0,0);update_tb();'> <img src='img/tool_sep.gif' height=19 width=1> <img class=fibut id='icrtr' src='img/tool_rrotate_bw.gif' title='Rotate Clockwise ($key[rrot])' onclick='ac_imgrotate(1)'><img class=fibut id='icrtl' src='img/tool_lrotate_bw.gif' title='Rotate Counterclockwise ($key[lrot])' onclick='ac_imgrotate(0)'></td></tr>";
		echo "<tr><td id=filmtd><div id=filmdiv><table id=filmtable><tr>";
		for($i=0;$i<count($f);$i++) {
			$img=htmlspecialchars($f[$i]['d'] || !preg_match("'^".join("|",$image_ext[$cfg["tnmethod"]])."$'",$f[$i]['mi'])?
				"img/icon_".$f[$i]['i']."48.gif":sxr_tnurl("$curdir/".$f[$i]['n']));
			$url=$f[$i]['d']?"goTo(&quot;".htmlspecialchars(shortenpath("$curdir/".$f[$i]['n']))."&quot;)":
				"location.href=&quot;dl.php?p=".urlenc(shortenpath("$curdir/".$f[$i]['n']))."&quot;";
			echo "<td class=fi1><div p=\"".$f[$i]['p']."\" title=\"".htmlspecialchars($f[$i]['n'])."\" class=tn onDblClick=\"$url\"><table class=t1><tr><td><img src=\"$img\"".(in_array($f[$i]['n'],$cutfiles)?" class=cut":"")."></td></tr></table><div class=t2>".htmlspecialchars($f[$i]['n'])."</div></div></td>\n";
		}
		echo "</tr></table></div></td></tr></table>";
	}

	function printdetail($f) {
		global $curdir,$cfg,$cutfiles,$order;
		$o1=substr($order,0,1);$o2=substr($order,1,1);
		echo "<table class=det><tr id=dettr><th width=100%>Name ".($o1=="n"?"<img src='img/img_arrow".($o2=="a"?"up":"down").".gif'>":"").
			"</th><th align=right>Size ".($o1=="s"?"<img src='img/img_arrow".($o2=="a"?"up":"down").".gif'>":"").
			"</th><th>Type ".($o1=="t"?"<img src='img/img_arrow".($o2=="a"?"up":"down").".gif'>":"").
			"</th><th>Date Modified ".($o1=="d"?"<img src='img/img_arrow".($o2=="a"?"up":"down").".gif'>":"").
			"</th><th align=right class=nosort>Permissions</th></tr>\n";
		for($i=0;$i<count($f);$i++) {
			$url=$f[$i]['d']?"goTo(&quot;".htmlspecialchars(shortenpath("$curdir/".$f[$i]['n']))."&quot;)":
				"location.href=&quot;dl.php?p=".urlenc(shortenpath("$curdir/".$f[$i]['n']))."&quot;";
			echo "<tr>
				<td><div p=\"".($f[$i]['p']&511)."\" title=\"".htmlspecialchars($f[$i]['n'])."\" class=de onDblClick=\"$url\"><img src=\"img/icon_".$f[$i]['i']."16.gif\"".
				(in_array($f[$i]['n'],$cutfiles)?" class=cut":"")."><span>".$f[$i]['n']."</span></div></td>
				<td align=right>".(isset($f[$i]['s'])?number_format($f[$i]['s']):"")."</td>
				<td>".$f[$i]['t']."</td>
				<td>".$f[$i]['m']."</td>
				<td align=right class=perms>".sxr_parseperms($f[$i]['p'])."</tr>\n";
		}
		echo '</table>';
	}

	function printthumb($f) {
		global $curdir,$cfg,$cutfiles,$image_ext;
		for($i=0;$i<count($f);$i++) {
			$img=htmlspecialchars($f[$i]['d'] || !preg_match("'^".join("|",$image_ext[$cfg["tnmethod"]])."$'",$f[$i]['mi'])?
				"img/icon_".$f[$i]['i']."48.gif":sxr_tnurl("$curdir/".$f[$i]['n']));
			$url=$f[$i]['d']?"goTo(&quot;".htmlspecialchars(shortenpath("$curdir/".$f[$i]['n']))."&quot;)":
				"location.href=&quot;dl.php?p=".urlenc(shortenpath("$curdir/".$f[$i]['n']))."&quot;";
			echo "<div p=\"".$f[$i]['p']."\" title=\"".htmlspecialchars($f[$i]['n'])."\" class=tn onDblClick=\"$url\"><table class=t1><tr><td><img src=\"$img\"".(in_array($f[$i]['n'],$cutfiles)?" class=cut":"")."></td></tr></table><div class=t2>".htmlspecialchars($f[$i]['n'])."</div></div>\n";
		}
	}

	function printicon($f) {
		global $curdir,$cfg,$cutfiles;
		for($i=0;$i<count($f);$i++) {
			$url=$f[$i]['d']?"goTo(&quot;".htmlspecialchars(shortenpath("$curdir/".$f[$i]['n']))."&quot;)":
				"location.href=&quot;dl.php?p=".urlenc(shortenpath("$curdir/".$f[$i]['n']))."&quot;";
			echo "<div p=\"".$f[$i]['p']."\" title=\"".htmlspecialchars($f[$i]['n'])."\" class=ic onDblClick=\"$url\"><div class=i1><img src=\"img/icon_".$f[$i]['i']."32.gif\"".(in_array($f[$i]['n'],$cutfiles)?" class=cut":"")."></div><div class=i2>".htmlspecialchars($f[$i]['n'])."</div></div>\n";
		}
	}
?>
	</td></tr></table>
	<table id=foot><tr>
	<?php
		echo "<td width=100%>".(count($files)-($curdir?1:0))." objects (Disk free space: ".sxr_format_fsize(disk_free_space(".")).")</td>
		<td align=right>".sxr_format_fsize($tsize)."</td>
		<td align=right>".php_uname()."</td>";
	?>
	</tr></table>
	</form>
<script type="text/javascript">
init();
</script>
</body>
</html>
