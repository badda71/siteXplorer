<?php
	define( '_VALID_SXR', 1 );
	error_reporting(0);
	if (file_exists("cfg.php")) {include "cfg.php";} else {include "cfg-dist.php";}
	include "lib.php";
	include "authn.php";

	function getImageLibs() {
		// do auto-detection on the available graphics libraries
		// This assumes the executables are within the shell's path
		$imageLibs= array();
		// do various tests:
		if (!(bool)ini_get('safe_mode')) {
			if ($i = _testIM()) $imageLibs['im'] = $i;
			if ($i = _testPBM()) $imageLibs['pbm'] = $i;
			if ($i = _testZip()) $imageLibs['zip'] = $i;
			if ($i = _testUnzip()) $imageLibs['uzip'] = $i;
			if ($i = _testJPEG()) $imageLibs['jpeg'] = $i;
		}
		$imageLibs['gd'] = _testGD();		
		return $imageLibs;
    }
	function _testJPEG() {
		@exec('jpegtran -v -? 2>&1', $output, $status);
		if (preg_match('/jpegtran[ \t,]+version[ \t]+([0-9a-b\.]+)/i',$output[0],$matches)) return $matches[0];
    }
	function _testIM() {
        @exec('convert -version 2>&1', $output, $status);
        if (!$status && preg_match('/imagemagick[ \t]+([0-9\.]+)/i',$output[0],$matches)) return $matches[0];
    }
	function _testPBM() {
        @exec('jpegtopnm -version 2>&1', $output, $status);
        if (!$status && preg_match('/netpbm[ \t]+([0-9\.]+)/i',$output[0],$matches)) return $matches[0];
    }
    function _testGD() {
        $gd = array();
		$GDfuncList = get_extension_funcs('gd');
		ob_start();
		@phpinfo(INFO_MODULES);
		$output=ob_get_contents();
		ob_end_clean();
		$matches[1]='';
        if (preg_match("/GD Version[ \t]*(<[^>]+>[ \t]*)+([^<>]+)/s",$output,$matches)) $gdversion = $matches[2];
        if ($GDfuncList) {
            if (in_array('imagegd2', $GDfuncList)) $gd['gd2'] = $gdversion;
			else $gd['gd1'] = $gdversion;
		}
		return $gd;
	}
	function _testZip() {
		@exec('zip -? 2>&1',  $output, $status);
		$output=join("\n",$output);
		if (preg_match("/zip[ \t]+([0-9\.]+)/is",$output,$matches)) return $matches[0];
    }
	function _testUnzip() {
		@exec('unzip -? 2>&1',  $output, $status);
		$output=join("\n",$output);
		if (preg_match("/unzip[ \t]+([0-9\.]+)/is",$output,$matches)) return $matches[0];
    }
	$il=getImageLibs();
	if (!$cfg["tnmethod"] ||
		($cfg["tnmethod"]==1 && !array_key_exists('im',$il)) ||
		($cfg["tnmethod"]==2 && !array_key_exists('gd2',$il['gd']))) {
		if (array_key_exists('im',$il)) $cfg["tnmethod"]=1;
		elseif (array_key_exists('pbm',$il)) $cfg["tnmethod"]=2;
		elseif (array_key_exists('gd2',$il['gd'])) $cfg["tnmethod"]=3;
		else $cfg["tnmethod"]=0;
	}
	if (!$cfg["jpegrot"] ||
		($cfg["jpegrot"]==2 && !array_key_exists('jpeg',$il))) {
		if (array_key_exists('jpeg',$il)) $cfg["jpegrot"]=2;
		else $cfg["jpegrot"]=1;
	}
	if (!$cfg["uzmethod"] ||
		($cfg["uzmethod"]==1 && !function_exists('gzopen')) ||
		($cfg["uzmethod"]==2 && !array_key_exists('uzip',$il))) {
		if (array_key_exists('uzip',$il)) $cfg["uzmethod"]=2;
		elseif (function_exists('gzopen')) $cfg["uzmethod"]=1;
		else $cfg["uzmethod"]=0;
	}
	if (!$cfg["zmethod"] ||
		($cfg["zmethod"]==1 && !function_exists('gzopen')) ||
		($cfg["zmethod"]==2 && !array_key_exists('zip',$il))) {
		if (array_key_exists('zip',$il)) $cfg["zmethod"]=2;
		elseif (function_exists('gzopen')) $cfg["zmethod"]=1;
		else $cfg["zmethod"]=0;
	}
?>
<form name=prefform action="index.php" method=post onsubmit="return ck_prefs()" style="margin:0px;padding:0px">
<input type=hidden name=curdir value="<?php echo htmlspecialchars($_REQUEST['curdir']);?>">
<input type=hidden name=order value="<?php echo $_REQUEST['order'];?>">
<input type=hidden name=action value=saveprefs>
<input type=hidden name="cfg[pass]" value="">
<input type=hidden name=view value="<?php echo $_REQUEST['view'] ?>">
<style>
.tab-pane td a {vertical-align: top; text-decoration: underline;}
</style>
<div class="tab-pane">
	<div class="tab-page"><h2 class="tab">General</h2>
	<table border=0 cellpadding=2 cellspacing=0 width=100%>
		<tr><td>Root Path</td><td><input size=40 name="cfg[root_path]" value="<?php echo htmlspecialchars($cfg["root_path"]); ?>"></td></tr>
		<tr><th colspan=2><div>Image Processing Options</div></th></tr>
		<tr><td>Thumbnailing library</td><td>
			<input type=radio name="cfg[tnmethod]" value=1<?php echo $cfg["tnmethod"]==1?" checked":""; echo array_key_exists('im',$il)?"":" disabled";?>>
				<a href="http://www.imagemagick.org" target=_blank>ImageMagick</a> -
				<?php if(array_key_exists('im',$il)) echo $il['im'];
				else echo '<strong>Not installed</strong>'; ?>
            <br>
			<input type=radio name="cfg[tnmethod]" value=2<?php echo $cfg["tnmethod"]==2?" checked":""; echo array_key_exists('pbm',$il)?"":" disabled";?>>
				<a href="http://netpbm.sourceforge.net/" target=_blank>Netpbm</a> -
				<?php if(array_key_exists('pbm',$il)) echo $il['pbm'];
				else echo '<strong>Not installed</strong>'; ?>
            <br>
			<input type=radio name="cfg[tnmethod]" value=3<?php echo $cfg["tnmethod"]==3?" checked":""; echo array_key_exists('gd2',$il['gd'])?"":" disabled";?>>
			GD2 library - 
			<?php if(array_key_exists('gd2',$il['gd'])) echo $il['gd']['gd2'];
			else echo '<strong>Not installed</strong>'; ?>
		</td></tr>
		<tr><td>Maximum thumbnail size</td><td><input size=5 name="cfg[thumb_max_x]" value="<?php echo htmlspecialchars($cfg["thumb_max_x"]); ?>"> x <input size=5 name="cfg[thumb_max_y]" value="<?php echo htmlspecialchars($cfg["thumb_max_y"]); ?>"></td></tr>
		<tr><td colspan=2><input id="ccbut" style="width:100px;float:right;" type="button" value="Clear Cache"<?php echo file_exists("thumbs")?"":" disabled"?> onClick="ac_clearcache()"><input type=checkbox name="cfg[enablecache]" value=1<?php echo $cfg["enablecache"]?" checked":"";?>> Enable thumbnail caching</td></tr>
		<tr><td>JPEG rotation</td><td>
			<input type=radio name="cfg[jpegrot]" value=1<?php echo $cfg["jpegrot"]==1?" checked":"";?>>
				Use thumbnailing library
            <br>
			<input type=radio name="cfg[jpegrot]" value=2<?php echo $cfg["jpegrot"]==2?" checked":""; echo array_key_exists('jpeg',$il)?"":" disabled";?>>
				<a href="http://www.ijg.org/" target=_blank>libjpeg</a> lossless rotation -
				<?php if(array_key_exists('jpeg',$il)) echo $il['jpeg'];
				else echo '<strong>Not installed</strong>'; ?>
		</td></tr>
		<tr><th colspan=2><div>Zipfile handling</div></th></tr>
		<tr><td>Zip method</td><td>
			<input type=radio name="cfg[zmethod]" value=2<?php echo $cfg["zmethod"]==2?" checked":""; echo array_key_exists('zip',$il)?"":" disabled";?>>
				<a href="http://www.info-zip.org/Zip.html" target=_blank>External Zip command</a> -
				<?php if(array_key_exists('zip',$il)) echo $il['zip'];
				else echo '<strong>Not installed</strong>'; ?>
            <br>
			<input type=radio name="cfg[zmethod]" value=1<?php echo $cfg["zmethod"]==1?" checked":""; echo function_exists('gzopen')?"":" disabled";?>>
				PHP zlib extension -
				<?php if(function_exists('gzopen')) echo 'installed';
				else echo '<strong>Not installed</strong>'; ?>
		</td></tr>
		<tr><td>Unzip method</td><td>
			<input type=radio name="cfg[uzmethod]" value=2<?php echo $cfg["uzmethod"]==2?" checked":""; echo array_key_exists('uzip',$il)?"":" disabled";?>>
				<a href="http://www.info-zip.org/UnZip.html" target=_blank>External Unzip command</a> -
				<?php if(array_key_exists('uzip',$il)) echo $il['uzip'];
				else echo '<strong>Not installed</strong>'; ?>
            <br>
			<input type=radio name="cfg[uzmethod]" value=1<?php echo $cfg["uzmethod"]==1?" checked":""; echo function_exists('gzopen')?"":" disabled";?>>
				PHP zlib extension -
				<?php if(function_exists('gzopen')) echo 'installed';
				else echo '<strong>Not installed</strong>'; ?>
		</td></tr>
	</table>
	</div>
	<div class="tab-page"><h2 class="tab">Security</h2>
	<table border=0 cellpadding=2 cellspacing=0 width=100%>
		<tr><th colspan=2><div>Authorized User</div></th></tr>
		<tr><td>Username</td><td><input size=40 name="cfg[user]" value="<?php echo htmlspecialchars($cfg["user"]); ?>"></td></tr>
		<tr><td>New Password</td><td><input type=password size=40 name="pass1" value=""></td></tr>
		<tr><td>Validate Password</td><td><input type=password size=40 name="pass2" value=""></td></tr>
	</table>
	</div>

	<div class="tab-page"><h2 class="tab">Keyboard</h2>
	<table border=0 cellpadding=2 cellspacing=0 width=100%>
		<tr><th colspan=4><div>Keyboard Shortcuts</div></th></tr>
		<tr><td width=25% align=right>Directory Up</td>
			<td width=25%><input name="key[dirup]" value="<?php echo $key["dirup"]?>" class=pkey autocomplete=off></td>
			<td width=25% align=right>Upload Files</td>
			<td width=25%><input name="key[upload]" value="<?php echo $key["upload"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Download Files</td><td>
			<input name="key[download]" value="<?php echo $key["download"]?>" class=pkey autocomplete=off></td>
			<td align=right>New Folder</td><td>
			<input name="key[mkdir]" value="<?php echo $key["mkdir"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Extract Zipfile</td><td>
			<input name="key[extract]" value="<?php echo $key["extract"]?>" class=pkey autocomplete=off></td>
			<td align=right>Select All</td><td>
			<input name="key[selall]" value="<?php echo $key["selall"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Invert Selection</td><td>
			<input name="key[dselall]" value="<?php echo $key["dselall"]?>" class=pkey autocomplete=off></td>
			<td align=right>Rename</td><td>
			<input name="key[rename]" value="<?php echo $key["rename"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Permissions</td><td>
			<input name="key[perms]" value="<?php echo $key["perms"]?>" class=pkey autocomplete=off></td>
			<td align=right>Delete</td><td>
			<input name="key[del]" value="<?php echo $key["del"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Cut</td><td>
			<input name="key[cut]" value="<?php echo $key["cut"]?>" class=pkey autocomplete=off></td>
			<td align=right>Copy</td><td>
			<input name="key[copy]" value="<?php echo $key["copy"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Paste</td><td>
			<input name="key[paste]" value="<?php echo $key["paste"]?>" class=pkey autocomplete=off></td>
			<td align=right>Filmstrip View</td><td>
			<input name="key[vfilm]" value="<?php echo $key["vfilm"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Thumbnails View</td><td>
			<input name="key[vthumb]" value="<?php echo $key["vthumb"]?>" class=pkey autocomplete=off></td>
			<td align=right>Icons View</td><td>
			<input name="key[vicon]" value="<?php echo $key["vicon"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Details View</td><td>
			<input name="key[vdet]" value="<?php echo $key["vdet"]?>" class=pkey autocomplete=off></td>
			<td align=right>Run Command</td><td>
			<input name="key[command]" value="<?php echo $key["command"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Preferences</td><td>
			<input name="key[prefs]" value="<?php echo $key["prefs"]?>" class=pkey autocomplete=off></td>
			<td align=right>Rotate Right</td><td>
			<input name="key[rrot]" value="<?php echo $key["rrot"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Rotate Left</td><td>
			<input name="key[lrot]" value="<?php echo $key["lrot"]?>" class=pkey autocomplete=off></td>
			<td align=right>Logout</td><td>
			<input name="key[logout]" value="<?php echo $key["logout"]?>" class=pkey autocomplete=off></td></tr>
		<tr><td align=right>Toggle Zoom</td><td>
			<input name="key[zoom]" value="<?php echo $key["zoom"]?>" class=pkey autocomplete=off></td>
			<td align=right></td><td></td></tr>
	</table>
	</div>
</div>
<div align="right">
	<input class=but type="submit" name="OK" value="OK"> <input class=but type="button" name="Cancel" value="Cancel" onClick="ca_prefs()">
</div></form>