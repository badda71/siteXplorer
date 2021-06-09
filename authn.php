<?php
defined( '_VALID_SXR' ) or die( 'Direct Access to this location is not allowed!' );
if (array_key_exists("user",$cfg) && $cfg["user"]) {
$msg=$me="";
$login_username=array_key_exists('login_username', $_REQUEST)?$_REQUEST['login_username']:(array_key_exists('login_username', $_COOKIE)?$_COOKIE['login_username']:"");
$login_password=array_key_exists('login_password', $_REQUEST)?$_REQUEST['login_password']:(array_key_exists('login_password', $_COOKIE)?$_COOKIE['login_password']:"");

if ($login_username) {
	$me=$login_username;
	if (strcmp($login_username,$cfg["user"])!=0 || ($cfg["pass"]!="" && strcmp(md5($login_password),$cfg["pass"])!=0)) {
		$me="";
		$msg="Falscher Benutzername oder Passwort";
	}
}

if ((array_key_exists('action', $_REQUEST) && $_REQUEST["action"]=="logout") || !$me)	 {
	// destroy the authentication cookie
	setcookie("login_username", '', time()-42000);
	setcookie("login_password", '', time()-42000);
	$login_username=$login_password=$me="";
}

if (!$me) {
	// login screen
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head><title>SiteXplorer Login</title>
	<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
	<link type="text/css" rel="StyleSheet" href="style.css.php">
	<script type="text/javascript" src="javascript.js"></script>
	</head>
	<body onLoad="document.getElementById('uname').focus();">
	<br><center>	
	<?php if ($msg)
		echo "<font color=#ff0000><b>$msg</b></font><br>&nbsp;";
	?>
<table id=f_login border=0 cellpadding=0 cellspacing=0><tr><td><img src=img/win_tl.gif></td><td bgcolor=#BDBDBD style='color:#4F4F4F' align=left><img src=img/img_logo16.gif style='margin-right:6px' align=absmiddle>SiteXplorer - Login</td><td><img src=img/win_tr.gif></td></tr><tr><td bgcolor=#BDBDBD></td><td style='border:1px solid white'>
	<div id=login style="width:320px">
	<table style="width:320px;height:242px;background:#ECE9D8;" cellpadding=0 cellspacing=0 border=0><tr><td><img src="img/authn.jpg"></td></tr><tr><td align=left>
	<form action="index.php" method="POST" name="login">
	<input type="hidden" name="action" value="login">
	<table border=0 cellpadding=2 cellspacing=0 style="margin:10px;height:162px;width:300px;">
	<tr><td colspan=2>Welcome to siteXplorer! Please log in.<br>&nbsp;</td></tr>
	<tr><td>User name:</td><td align=center valign=middle><input id=uname type=text name="login_username" style="width:148px;height:15px;border:1px solid #7F9DB9;background:#ffffff url(img/user.gif) no-repeat 2px 2px;padding-left:22px;padding-top:3px"></td></tr>
	<tr><td>Password:</td><td align=center><input type=password name="login_password" style="width:165px;height:15px;border:1px solid #7F9DB9;padding-top:3px;padding-left:5px;"></td></tr>
	<tr><td colspan=2><input type="checkbox" name="staylogged" value="yes"> Stay logged in</td></tr>
	<tr><td colspan=2 align=right valign=bottom><input type=submit value="OK" class=but></td></tr>
	</table></form></td></tr></table></div>
</td><td bgcolor=#BDBDBD></td></tr><tr><td><img src=img/win_bl.gif></td><td bgcolor=#BDBDBD></td><td><img src=img/win_br.gif></td></tr></table>	
	</center>
	</body>
	</html>
	<?php
	exit();

} else if ($_REQUEST["action"]=="login") {
	if (array_key_exists("staylogged", $_REQUEST) && $_REQUEST["staylogged"]=="yes") {
		setcookie("login_username", $login_username, time()+60*60*24*365*10);
		setcookie("login_password", $login_password, time()+60*60*24*365*10);
	} else {
		setcookie("login_username", $login_username);
		setcookie("login_password", $login_password);
	}
}
}
?>