<?php
	define( '_VALID_SXR', 1 );
	error_reporting(0);
	if (file_exists("cfg.php")) {include "cfg.php";} else {include "cfg-dist.php";}
	header("Content-type: text/css");
?>
/* <style> */
/* general styles */
body {
	background:		#ffffff url(img/back.jpg) no-repeat fixed bottom right;
	margin:			0px;
}
a, td, body, input, select, th {
	font-size:		11px;
	font-family:	Tahoma, "Lucida Grande CE", lucida, sans-serif;
	color:			black;
	text-decoration:none;
	vertical-align:	middle;
}
img {
	border:			none;
}
table {
	border-collapse:collapse;
	padding:		0px;
}
#main {
	cursor:			default;
	width:			99.5%;
}
#back {
	background-color: #ffffff;
	opacity:		.50;
	filter:			alpha(opacity=50);
	-moz-opacity:	0.5;
	width:			100%;
	height:			100%;
	position:		fixed;
	display:		none;
	top:			0px;
	left:			0px;
	z-index:		900;
}
/* toolbar */
#logo {
	border-left:	2px outset #ffffff;
	padding:		4px;
	position:		relative;
}
#logo img {
	cursor:			pointer;}
table#head {
	border-collapse:	collapse;
	width:			99.5%;
	background:		#EBEADB;
}
td.toolbar {
	border-bottom:	2px outset #ffffff;
	padding-left: 4px;
	padding-right: 4px;
}
td.toolbar img {
	vertical-align:	middle;
	position:	relative;
	padding:	3px;
	border:		1px solid #EBEADB;
}
td.a1 {
	padding:	5px;
	color:		#808080;
}
td.a2 {
	width:		100%;
	padding:	2px;
}
div.a2 {
	border:		1px solid #cccccc;
	background:	#ffffff;
	padding: 2px;
}
div.a2 img {
	vertical-align: middle;}
/* zoom box */
#f_zoom {
	position:		absolute;
	visibility:		hidden;
	top:			10px;
	left:			10px;
	z-index:		850;
}
#zoomtd {		
	cursor:			url(img/cur_unzoom.gif),url(img/cur_unzoom.ico),pointer;
	background-color:	#EEF2FB;
}
#zoomtitle {
	margin-right:	50px;
	white-space:	nowrap;
	overflow:		hidden;
}
/* about box */
#f_about {
	position:		absolute;
	display:		none;
	top:			5px;
	right:			10px;
	z-index:		950;
}
#about {
	padding:		3px;
	padding-left:	5px;
	width:			370px;
	height:			90px;
	background:		#EBEADB;
}
#about div {
	font-size:		20px;
	font-weight:	bold;
	margin-bottom:	10px;}
#about img {
	float:			right;}
#about a {
	color:			#0000ff;
	vertical-align:	bottom;}
/* icons */
/* details */
.det th {
	text-align:		left;
	border-right:	2px outset #ffffff;
	border-bottom:	3px solid #CBC7B8;
	background:		#EBEADB;
	padding-right:	3px;
	padding-left:	3px;
	white-space:	nowrap;
}
.det th img {
	vertical-align:	middle;}
.det td {
	height:			16px;
	padding:		0px;
	padding-right:	3px;
	padding-left:	3px;
	white-space:	nowrap;
	vertical-align:	bottom;
}
.det img {
	margin-right:	2px;
	vertical-align:	bottom;
}
.det div {
	border:			1px solid white;
	overflow:		hidden;
	height:			16px;
}
.perms {
	font-family:	"Lucida Console", "Monaco CE", fixed, monospace
}
/* Filmstrip view */
#film {
	width:				100%;
	margin:				0px;
	border-collapse:	collapse;
	padding:			0px;
}
#filmprevtd {
	background-color:	#EEF2FB;
	overflow:			hidden;
}
#filmimg {
	cursor:		url(img/cur_zoom.gif),url(img/cur_zoom.ico),pointer;
	border:		1px solid black;
	display:	none;
}
#filmld {display: none;}
#filmnav {
	height:				42px;
	background-color:	#EEF2FB;
	text-align:			center;
}
#filmnav img {
	border:		1px solid #EEF2FB;
	position:	relative;
}
#filmtd {
	height:				158px;
	vertical-align:		top;
	white-space:		nowrap;
	padding:			0px;
}
#filmdiv {
	width:				99.5%;
	height:				158px;
	position:			absolute;
	overflow:			auto;
	padding:			0px;
}
#filmtable {
	margin:				0px;
	border-collapse:	collapse;
	padding:			0px;
}
td.fi1 {
	padding:			0px;
}
/* thumbnails */
.tn {
	border:				1px solid white;
	padding:			0px;
	float:				left;
	margin:				4px;
	width:				<?php echo $cfg["thumb_max_x"]+23;?>px;
	text-align:			center;
	height:				<?php echo $cfg["thumb_max_y"]+37;?>px;
}
.t1 {
	margin-top:			4px;
	margin-left:		auto;
	margin-right:		auto;
	border:				1px solid #cccccc;
	width:				<?php echo $cfg["thumb_max_x"]+2;?>px;
	height:				<?php echo $cfg["thumb_max_y"]+2;?>px;
}

.t1 td {
	background:			#ffffff;
	padding:			0px;
	text-align:			center;
	vertical-align:		middle;
}
.t2 {
	margin-top:			4px;
	text-align:			center;
	padding:			0px;
	width:				<?php echo $cfg["thumb_max_x"]+23;?>px;
	height:				27px;
	overflow:			hidden;
}
/* icons */
.ic {
	border:				1px solid white;
	padding:			0px;
	float:				left;
	margin:				4px;
	width:				67px;
	height:				67px;
}
.i1 {
	margin-top:			4px;
	width:				67px;
	height:				32px;
	padding:			0;
	text-align:			center;
}
.i2 {
	margin-top:			4px;
	text-align:			center;
	padding:			0px;
	width:				67px;
	height:				27px;
	overflow:			hidden;
}

/* others */
.warning {
	padding:		3px;
	width:			90%;
	border:			1px solid red;
	margin:			4px;
	background-color: #ffffff;
	font:			11px monospace;
	text-align:		left;
}
.notice {
	padding:		3px;
	width:			90%;
	border:			1px solid black;
	margin:			4px;
	background-color: #ffffff;
	font:			11px monospace;
	text-align:		left;
}
.cut {
	opacity:		.50;
	filter:			alpha(opacity=50);
	-moz-opacity:	0.5;
}
/* upload box */
#f_upload {
	position:		absolute;
	display:		none;
	z-index:		950;
}
#upload {
	padding:		4px;
	background:		white;
}
#ulfiles {
	margin-bottom:	5px;
	margin-top:		5px;}
.ulfile {
	margin-top:	3px;
}
.ulfile span {
	padding-left:	5px;
	display:		none;
}
.but {
	padding-bottom:	2px;
	width:			76px;
	height:			23px;
}
/* footer */
#foot {
	border-collapse:	collapse;
	width:			99.5%;
	background:		#EBEADB;
	border-top:		3px solid #CBC7B8;
}
#foot td {
	padding:		3px;
	border-right:	2px outset #ffffff;
	white-space:	nowrap;
}
#upspin {
	display:none;top:0px;left:0px;width:100%;height:100%;position:absolute;background-color:#ffffff;opacity:.50;filter:alpha(opacity=50);-moz-opacity:0.5;
}
/* tabbed tables and config styles*/
.dynamic-tab-pane-control.tab-pane {
	position:	relative;
	width:		100%;		/* width needed weird IE bug */
	margin-right:	-2px;	/* to make room for the shadow */
}
.dynamic-tab-pane-control .tab-row .tab {
	width:				70px;
	height:				16px;
	background-image:	url( "img/tab.png" );
	position:		relative;
	top:			0;
	display:		inline;
	float:			left;
	overflow:		hidden;
	cursor:			Default;
	margin:			1px -1px 1px 2px;
	padding:		2px 0px 0px 0px;
	border:			0;
	z-index:		1;
	font:			11px Tahoma;
	white-space:	nowrap;
	text-align:		center;
}
.dynamic-tab-pane-control .tab-row .tab.selected {
	width:				74px !important;
	height:				18px !important;
	background-image:	url( "img/tab.active.png" ) !important;
	background-repeat:	no-repeat;

	border-bottom-width:	0;
	z-index:		3;
	padding:		2px 0 0px 0;
	margin:			1px -3px -3px 0px;
	top:			-2px;
	font:				11px Tahoma;
}
.dynamic-tab-pane-control .tab-row .tab a {
	font:				11px Tahoma;
	color:				Black;
	text-decoration:	none;
	cursor:				default;
}
.dynamic-tab-pane-control .tab-row .tab.hover {
	font:				11px Tahoma;
	width:				70px;
	height:				16px;
	background-image:	url( "img/tab.hover.png" );
	background-repeat:	no-repeat;
}
.dynamic-tab-pane-control .tab-page, .input-pane {
	clear:			both;
	border:			1px solid rgb( 145, 155, 156 );
	background:		rgb( 252, 252, 254 );
	z-index:		2;
	position:		relative;
	top:			-2px;

	font:				11px Tahoma;
	color:				Black;
	padding:		10px;
}

.dynamic-tab-pane-control .tab-row {
	z-index:		1;
	white-space:	nowrap;
}
/* preferences */
#f_prefs {
	top:			40px;
	left:			40px;
	position:		absolute;
	display:		none;
	z-index:		950;
}
#prefs {
	width:			407px;
	padding:		4px;
	background:		#EBEADB;
}
#prefs th {
	text-align:			left;
	font-weight:		bold;
	height:				20px;
	background-color:	#cccccc;
	padding-left:		5px;
}
#prefs td {
	vertical-align:		top;
}
input.pkey {
	width: 100px;}