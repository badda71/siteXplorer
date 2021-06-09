<?php
define( '_VALID_SXR', 1 );
error_reporting(0);
include "cfg.php";
include "lib.php";
include "authn.php";

chdir($cfg["root_path"]);
$p = preg_replace("|^/+|","",utf8_decode(shortenpath($_REQUEST['p'])));
if (!file_exists($p)) {header("HTTP/1.0 404 Not Found");exit;}

$i = pathinfo($p);
$mime=file2mime($p);

// should we convert to another image format?
// if conversion ok AND format not supported by browser AND format supported by imglib
if ($_REQUEST["cok"] && !in_array($mime,$browserimg) && preg_match("'^(".join("|",$image_ext[$cfg["tnmethod"]]).")$'",$mime)) {
	// get my target format from img_map
	foreach ($img_map as $k => $v) if (preg_match('|^'.$k.'$|',$mime)) {$tf=$v;break;}
	// now convert
	if ($tf) {
		header("Content-type: $tf");
		header("Content-disposition: inline; filename=\"$i[basename].".array_search($tf,$mimes)."\"");
		if ($cfg["tnmethod"]==1) passthru("convert \"$p\" ".array_search($tf,$mimes).":-");
		elseif ($cfg["tnmethod"]==2) passthru($pbm_commands[$mime][0]." \"$p\" | ".$pbm_commands[$tf][1]);
		elseif ($cfg["tnmethod"]==3 && $img=imagecreatefromstring(file_get_contents($p))) eval ('image'.preg_replace('|^.*[\./]|',"",$tf).'($img);');
		exit;
	}
}
header("Content-type: $mime");
header("Content-disposition: inline; filename=\"$i[basename]\"");
readfile($p);
?>