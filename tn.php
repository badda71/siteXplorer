<?php
	define( '_VALID_SXR', 1 );
	error_reporting(0);
	include "cfg.php";
	include "lib.php";
	include "authn.php";

	function myerror() {
		global $icons,$p,$mime;
		foreach ($icons as $key => $value) {	# get my icon
			if (preg_match("'^".$key."$'i",$mime)) break;
		}
		header("Location: img/icon_".$value[0]."48.gif");
		exit;
	}
	function mymkdir ($base,$mk) {
		$i="";
		foreach (explode("/",$mk) as $d) {$i.="/".$d;if (!file_exists("$base$i")) mkdir ("$base$i");}
	}
	$cwd=getcwd();
	chdir($cfg["root_path"]);
	$p = preg_replace("|^/+|","",utf8_decode(shortenpath($_REQUEST['p'])));
	if (!file_exists($p)) {header("HTTP/1.0 404 Not Found");unlink($tp);exit;}

	// are we caching and is the cache up-to-date?
	$tp=$cfg["enablecache"]?"$cwd/thumbs/$p":"";
	if ($tp && file_exists($tp) && filemtime($tp)==filemtime($p)) {header("Location: thumbs/".urlenc($p));exit;}

	// should we handle this image at all?
	$mime=file2mime($p);
	if (!preg_match("'^(".join("|",$image_ext[$cfg["tnmethod"]]).")$'",$mime)) myerror();

	// make the thumbnail
	$i = pathinfo($p);
	if ($tp && !file_exists("$cwd/thumbs/$i[dirname]")) mymkdir($cwd,"thumbs/$i[dirname]");
	// get my target format from img_map
	if (in_array($mime,$browserimg)) $tf=$mime;
	else foreach ($img_map as $k => $v) if (preg_match('|^'.$k.'$|',$mime)) {$tf=$v;break;}
	
	if ($cfg["tnmethod"]==1) {
		$com="convert \"$p\" -resize \"$cfg[thumb_max_x]x$cfg[thumb_max_y]>\" ".array_search($tf,$mimes).":".($tp?"\"$tp\"":"-");
		if ($tp) {exec($com);}
		else {
			header("Content-type: $tf");
			passthru($com);
		}
	} elseif ($cfg["tnmethod"]==2) {
		if (!($s=getimagesize($p)) || $s[0]<1 || $s[1]<1) myerror();
		$com=$pbm_commands[$mime][0]." \"$p\"".
			($cfg["thumb_max_x"]<$s[0] || $cfg["thumb_max_y"]<$s[1]?" | pnmscale -xysize $cfg[thumb_max_x] $cfg[thumb_max_y]":"").
			" | ".$pbm_commands[$tf][1].($tp?" >\"$tp\"":"");
		if ($tp) {exec($com);}
		else {
			header("Content-type: $tf");
			passthru($com);
		}
	} elseif ($cfg["tnmethod"]==3) {
		if (!($s=getimagesize($p)) || $s[0]<1 || $s[1]<1) myerror();
		$percent=min($cfg["thumb_max_x"]/$s[0],$cfg["thumb_max_y"]/$s[1],1);
		$newwidth = $s[0] * $percent;
		$newheight = $s[1] * $percent;
		if (!($img=imagecreatefromstring(file_get_contents($p)))) myerror();
		imagecopyresampled(
			$thumb = imagecreatetruecolor($newwidth, $newheight),
			$img,
			0, 0, 0, 0, $newwidth, $newheight, $s[0], $s[1]);
		if (!$tp) header("Content-type: $tf");
		eval ('image'.preg_replace('|^.*[\./]|',"",$tf).'($thumb'.($tp?',$tp':'').');');
	} else myerror();
	if ($tp) {
		if (!file_exists($tp)) myerror();
		touch ($tp,filemtime($p));
		header("Location: thumbs/".urlenc($p));
	}
?>
