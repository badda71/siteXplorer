// global vars
var items=new Array();
var sel=0; // amount of selected items
var curpos=0; // current cursor position (1..xx)
var shiftpos=0; // last selection position
var dotre=/^\.\.?$/;
var winheight=0,winwidth=0;	// current viewport height, width & scroll
var acdiv=0;	// which dialog div is currently active?
var prefsloaded=false;

// Preload images
var pimg=new Image();pimg.src="img/spinner.gif";

// global vars and functions for slideshow handling
var height;	// height of preview table cell
var imgsloaded=false;	// var to indicate if all thumbnails are loaded
var zoomactive=false;	// zoom mode?
var imgCache=new Array();	// image cache for preview images
var lastimgname=null;	// last image loaded in preview
var picStatus=0;	// 0:no img, 1: img loading, 2: image loaded

function handleResize(e) {
	calc_windims();
	if (view=="s") {
		height=winheight;
		height-=document.getElementById("head").offsetHeight;
		height-=document.getElementById("foot").offsetHeight;
		height-=document.getElementById("filmnav").offsetHeight;
		height-=document.getElementById("filmtd").offsetHeight;
		height-=3;
		height=height<0?0:height;
		document.getElementById("filmprevtd").style.height=height+"px";
	}
	update_pics();
}
function ac_imgrotate(clockwise) {
	if (document.getElementById("icrtr").src.match(/_bw\./)) return;
	document.f.arg.value=items[curpos-1].title;
	document.f.action.value="rotate"+clockwise;
	subfrm();
}
function toggle_zoom() {
	zoomactive=!zoomactive;
	update_preview();
}
function update_pics () {
	var img=imgCache[items[curpos-1].title];
	// update zoom
	var zf=document.getElementById("f_zoom");
	if (zoomactive) {
		var itd=view=="s"?document.getElementById("filmprevtd"):null;
		var z=document.getElementById("zoomimg");
		var zld=document.getElementById("zoomld");
		var zno=document.getElementById("zoomno");
		var zd=document.getElementById("zoomdiv");
		var ztd=document.getElementById("zoomtd");
		var zt=document.getElementById("zoomtitle");
		// set up visibilities
		zf.style.visibility="hidden";
		if (picStatus==0 || !img) {
			z.style.display=zld.style.display="none";
			zno.style.display="block";
			zt.innerHTML="SiteXplorer - Zoom";
		} else if (picStatus==1) {
			z.style.display=zno.style.display="none";
			zld.style.display="block";
			zt.innerHTML="SiteXplorer - Loading";
		} else {
			z.src=img.src;
			zld.style.display=zno.style.display="none";
			z.style.display="block";
			zt.innerHTML="SiteXplorer - "+items[curpos-1].title;
		}

		// set up size
		// height
		if (zd.offsetHeight<(itd?itd.offsetHeight:winheight)-36) ztd.style.height=Math.min(236,(itd?itd.offsetHeight:winheight)-36)+"px";
		if (zd.offsetWidth<Math.min((itd?itd.offsetWidth:winwidth)-15,200)) ztd.style.width=Math.min((itd?itd.offsetWidth:winwidth)-15,200)+"px";

		// set up position
		var ys=document.documentElement?document.documentElement.scrollTop:(self.pageYOffset?self.pageYOffset:document.body.scrollTop);
		if (itd) {
			var y=0,e=itd;
			while(e) {y+=e.offsetTop;e=e.offsetParent;}
			zf.style.top=Math.max(ys,ys+y+itd.offsetHeight/2-zf.offsetHeight/2-11)+"px";
		} else {
			zf.style.top=Math.max(ys,ys+winheight/2-zf.offsetHeight/2-11)+"px";
		}
		zf.style.left=Math.max(0,Math.round(((itd?itd.offsetWidth:winwidth)-zf.offsetWidth)/2)+1)+"px";
		// show
		zf.style.visibility="visible";
	} else zf.style.visibility="hidden";
	// update slideshow preview area
	if (view=="s") {
		var pi=document.getElementById("filmimg");
		if (picStatus==0 || !img) {
			pi.style.display=document.getElementById("filmld").style.display="none";
			document.getElementById("filmno").style.display="block";
		} else if (picStatus==1) {
			pi.style.display=document.getElementById("filmno").style.display="none";
			document.getElementById("filmld").style.display="block";
		} else {
			var e=document.getElementById("filmprevtd");
			var mh=height-10;mh=mh<0?0:mh;
			var mw=Math.min(e.offsetWidth,winwidth)-12;
			var f=Math.min(1,mh/img.height,mw/img.width);
			pi.src=img.src;
			pi.style.height=Math.round(img.height*f)+"px";
			pi.style.width=Math.round(img.width*f)+"px";
			document.getElementById("filmno").style.display=document.getElementById("filmld").style.display="none";
			pi.style.display="block";
		}
	}
}

function update_preview(e) {
	if (!e) var e = window.event;
	var n=items[curpos-1].title;

	// an image has loaded/aborted/errored - do something
	if (e && (e.type=="load"||e.type=="error"||e.type=="abort")) {
		if (e.type=="load" && this==imgCache[n]) picStatus=2;
		else if (e.type=="error") {if (this==imgCache[n]) picStatus=0;this.name="";}
		else if (e.type=="abort") {if (this==imgCache[n]) picStatus=0;delete imgCache[this.name];}
	} else if (n.match(imgCanShowRe)) {
		if (!imgCache[n]) {	// image is not yet cached
			// before loading a new image, we're canceling out of the last if it is not yet loaded
			if (lastimgname && imgCache[lastimgname] && !imgCache[lastimgname].complete) {
				//imgCache[lastimgname].onerror=null;imgCache[lastimgname].src="";delete imgCache[lastimgname];
			}
			imgCache[n]=new Image();
			picStatus=1;update_pics(); // set loading icon
			lastimgname=imgCache[n].name=n;
			var url="dl.php?cok=1&p="+curdirenc+"/"+myescape(items[curpos-1].title);
			if (!imgsloaded) {
				var srcArray=new Array();
				var elementArray=new Array();
				var allimages=document.getElementsByTagName("img");
				// clear the image download queue
				for (var i=0;i<allimages.length;i++){
					if (allimages[i].src!=url && !allimages[i].complete) {
						srcArray.push(allimages[i].src);
						elementArray.push(allimages[i]);
						allimages[i].src="";
					}
				}
			}
			// set the new preview imgage src
			imgCache[n].onerror=imgCache[n].onabort=imgCache[n].onload=update_preview;
			imgCache[n].src=url;
			// restore the image download queue
			if (!imgsloaded) {
				if (!srcArray.length) imgsloaded=true;
				while (srcArray.length>0) elementArray.shift().src=srcArray.shift();
			}
			return;
		}
		else if (!imgCache[n].name) picStatus=0;
		else if (!imgCache[n].complete) picStatus=1;
		else picStatus=2;
	} else picStatus=0;
	update_pics();
}

// common variables and functions
Array.prototype.binarySearch = function(item) {
	var left = -1,right = this.length,mid;

	while(right - left > 1) {
		mid = (left + right) >>> 1;
		if(this[mid] < item) left = mid;
		else right = mid;
	}
	if(this[right] != item) return -(right + 1);
	return right;
}
function init() {
	var a=document.getElementById("main").getElementsByTagName("div");
	var i,c;
	for (i=0;i<a.length;i++) { // set up item list
		if (a[i].className=="tn" || a[i].className=="ic" || a[i].className=="de") {
			items.push(a[i]);
			if (curitem && a[i].title==curitem) cpos=items.length;
			a[i].sxnr=items.length;
			a[i].sxsel=false;
		}
	}
	cpos=cpos>items.length?items.length:cpos<1?1:cpos;
	if (view=="s") document.getElementById('filmtable').onmouseup=handleClick;
	else document.getElementById('main').onmouseup=handleClick;
	document.onkeydown=handleKey;
	// setup details view header cells
	if (view=="d") {
		a=document.getElementById("dettr");
		for (i=0;i<a.childNodes.length;i++) {
			if (a.childNodes[i].className=="nosort") continue;
			a.childNodes[i].onmouseover=dethover;
			a.childNodes[i].onmouseout=detout;
			a.childNodes[i].onmouseup=sortnew;
		}
	}
	docfocus();
	calc_windims();
	if (cpos) doSelection(cpos,0,0,0); else move_cursor(1,1);
	update_tb();
	i=document.getElementById("f_upload").style;
	a=findPos(document.getElementById("icul"));
	i.top=(a[1]+32)+"px";
	i.left=(a[0])+"px";
	window.onresize=handleResize;
	window.onresize(null);
	if (firststart) ac_prefs();
}
function calc_windims() {
	winheight=document.documentElement?document.documentElement.clientHeight:(self.innerHeight?self.innerHeight:document.body.clientHeight);
	winwidth=document.documentElement?document.documentElement.clientWidth:(self.innerWidth?self.innerWidth:document.body.clientWidth);
}
function move_cursor(npos,mem) {
	var e;
	if (curpos) items[curpos-1].style.border=items[curpos-1].sxsel?"1px solid #316AC5":"1px solid #FFFFFF";
	curpos=npos;
	items[curpos-1].style.border="1px dashed #808080";
	if (mem) shiftpos=curpos;

	// autoscroll
	if (view=="s") {
		// horizontal scrolling in filstrip view
		e=document.getElementById("filmdiv");
		var xt = -e.scrollLeft;
		var p=items[curpos-1];
		while(p.offsetParent) {xt+=p.offsetLeft;p=p.offsetParent;}
		if (xt<0) e.scrollLeft+=xt;
		else {
			xt+=items[curpos-1].offsetWidth;
			xt-=e.offsetWidth;
			if (xt>0) e.scrollLeft+=xt;
		}
	} else {
		// vertical scrolling
		var yt = -(document.documentElement?document.documentElement.scrollTop:(self.pageYOffset?self.pageYOffset:document.body.scrollTop));
		var p=items[curpos-1];
		while(p.offsetParent) {yt+=p.offsetTop;p=p.offsetParent;}
		if (yt<0) window.scrollBy(0,yt);
		else {
			yt+=items[curpos-1].offsetHeight;
			yt-=winheight;	
			if (yt>0) scrollBy(0,yt);
		}
	}
	if (view=="s" || zoomactive) update_preview(null);
}
function myescape(i) {return escape(i).replace(/\+/g,"%2B");}
function doSelection (nr,shift,ctrl,sel) { // nr 1-xx
	var i;
	if (!ctrl) deselectAll(false);
	if (shift && shiftpos) {
		var ac=items[shiftpos-1].sxsel;
		ac=ctrl?!ac:ac;
		for (i=Math.min(shiftpos,nr);i<=Math.max(shiftpos,nr);i++) (ac && sel==0 || sel==-1)?deselect(i-1):select(i-1);
		move_cursor(nr,0);
	} else {
		(items[nr-1].sxsel && sel==0 || sel==-1)?deselect(nr-1):select(nr-1);
		move_cursor(nr,1);
	}
}
function select (i) { // i 0-xx
	if (items[i].title.match(dotre)) return;
	if (!items[i].sxsel) sel++;
	items[i].sxsel=true;
	items[i].style.background="#316AC5";
	items[i].lastChild.style.color="#FFFFFF";
	items[i].style.border=i+1==curpos?"1px dashed #808080":"1px solid #316AC5";
}
function deselect (i) { // i 0-xx
	if (items[i].sxsel) sel--;
	items[i].sxsel=false;
	items[i].style.background="#FFFFFF";
	items[i].lastChild.style.color="#000000";
	items[i].style.border=i+1==curpos?"1px dashed #808080":"1px solid #FFFFFF";
}
function selectAll (resetcur) {
	for (var i=0;i<items.length;i++) if (!items[i].sxsel && items[i].title!="..") select (i);
	if (resetcur) move_cursor(1,1);
}
function deselectAll (resetcur) {
	for (var i=0;i<items.length;i++) if (items[i].sxsel) deselect (i);
	if (resetcur) move_cursor(1,1);
}
function handleClick (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	while (c!=document && !c.sxnr) c=c.parentNode;
	if (c==document) {
		deselectAll(true);
	} else doSelection(c.sxnr,e.shiftKey,e.ctrlKey,0);
	clearSelection();
	update_tb();
	return false;
}
function xItems() {
	var x;
	for (x=1;x<items.length;x++) if (items[x].offsetLeft==items[0].offsetLeft) break;
	return x;
}
function yItems() {
	return Math.floor(winheight/items[0].offsetHeight)-1;
}
function handleKey (e) {
	if (!e) var e = window.event;
	var k=e.keyCode,x=0,c=e.srcElement?e.srcElement:e.target;
	if (acdiv==0) {
		if (c.name=="view") return true;
		else if (c.name=="command") {
			if (k==13) {ac_command();return false;}
			if (k==27) {c.value="";docfocus();return false;}
			return true;
		}
		else if (c.name=="arg") {
			if (k==13) {do_rename();return false;}
			if (k==27) {ca_rename();return false;}
			return true;
		}
		// check fixed keys (cursor, pgup, pgdown, end, home)
		else if (k>=33 && k<=40 && !e.altKey) {
			if (k==33)	{if (curpos==1) return true;x=Math.max(curpos-xItems()*yItems(),1);}	// pgup
			else if (k==34)	{if (curpos==items.length) return true;x=Math.min(curpos+xItems()*yItems(),items.length);} // pgdown
			else if (k==35) {if (curpos==items.length) return true;x=items.length;} // end
			else if (k==36) {if (curpos==1) return true;x=1;}	// home
			else if (k==37)	{x=Math.max(curpos-1,1);} // left
			else if (k==38)	{if (curpos==1) return true;x=Math.max(curpos-xItems(),1);}	// up
			else if (k==39)	{x=Math.min(curpos+1,items.length);}	// right
			else if (k==40)	{if (curpos==items.length) return true;x=Math.min(curpos+xItems(),items.length);}	// down
			if (curpos!=x) {
				if (e.ctrlKey && !e.shiftKey) move_cursor(x,0);
				else doSelection(x,e.shiftKey,e.ctrlKey,0);
				update_tb();
			}
			return false;
		}
		else if (k==13)	{items[curpos-1].ondblclick();return false;}// return
		else if (k==32)	{doSelection(curpos,e.shiftKey,e.shiftKey?e.ctrlKey:1,e.ctrlKey?0:1);return false;} // space
		// check configurable keys
		for (i=0;i<k_action.length;i++) if (k==k_code[i] && e.ctrlKey==k_ctrl[i] && e.altKey==k_alt[i] && e.shiftKey==k_shift[i]) break;
		if (i<k_action.length) {
			switch (k_action[i]) {
				case "del":		ac_delete();return false;
				case "mkdir":	ac_mkdir();return false;
				case "selall":	ac_sall();return false;
				case "dselall":	ac_dsall();return false;
				case "command":	document.f.command.focus();return false;
				case "vfilm":	document.f.view.selectedIndex=0; document.f.view.onchange(); return false;
				case "vthumb":	document.f.view.selectedIndex=1; document.f.view.onchange(); return false;
				case "vicon":	document.f.view.selectedIndex=2; document.f.view.onchange(); return false;
				case "vdet":	document.f.view.selectedIndex=3; document.f.view.onchange(); return false;
				case "upload":	ac_upload();return false;
				case "copy":	ac_copy(0);return false;
				case "download":ac_download();return false;
				case "paste":	ac_paste();return false;
				case "cut":		ac_copy(1);return false;
				case "extract":	ac_extract();return false;
				case "perms":	ac_chmod();return false;
				case "prefs":	ac_prefs();return false;
				case "rename":	ac_rename();return false;
				case "rrot":	if (view=="s") {ac_imgrotate(1);return false;} else break;
				case "lrot":	if (view=="s") {ac_imgrotate(0);return false;} else break;
				case "zoom":	toggle_zoom();return false;
				case "logout":	ac_logout();return false;
				case "dirup":	goTo(curdir+"/..");return false;
			}
		}
		if (!e.shiftKey && !e.altKey && !e.ctrlKey) {ac_search(String.fromCharCode(k));return false;}
	} else if (acdiv==1) {
		if (k==27) {ca_upload();return false;}
		if (k==13) {do_upload();return false;}
	} else if (acdiv==2) {
		if (k==27) {ca_prefs();return false;}
		if (k==13) {document.prefform.submit();return false;}
	} else if (acdiv==3) {
		if (k==27 || k==13) {ca_about();return false;}
	}
	return true;
}
function clearSelection() {
	if(document.selection && document.selection.empty) document.selection.empty();
	else if(window.getSelection) {
		var sel=window.getSelection();
		if(sel && sel.removeAllRanges) sel.removeAllRanges();
	}
}
function update_tb() {
	var i,e,c;
	// dis-, enable toolbar icons
	if (sel) {
		document.getElementById("icdel").src="img/tool_delete.gif";
		document.getElementById("iccut").src="img/tool_cut.gif";
		document.getElementById("iccpy").src="img/tool_copy.gif";
		document.getElementById("icdl").src="img/tool_download.gif";
		document.getElementById("icchm").src="img/tool_chmod.gif";
	} else {
		document.getElementById("icdel").src="img/tool_delete_bw.gif";
		document.getElementById("iccut").src="img/tool_cut_bw.gif";
		document.getElementById("iccpy").src="img/tool_copy_bw.gif";			
		document.getElementById("icdl").src="img/tool_download_bw.gif";
		document.getElementById("icchm").src="img/tool_chmod_bw.gif";
	}
	if (items[curpos-1].title.match(/\.zip$/i)) document.getElementById("icext").src="img/tool_extract.gif"
	else document.getElementById("icext").src="img/tool_extract_bw.gif"
	if (sel>=items.length-(curdir?1:0)) document.getElementById("icsa").src="img/tool_selectall_bw.gif";
	else document.getElementById("icsa").src="img/tool_selectall.gif";
	if (cb_action) document.getElementById("icpst").src="img/tool_paste.gif";
	else document.getElementById("icpst").src="img/tool_paste_bw.gif";
	if (items[curpos-1].title=="..") document.getElementById("icren").src="img/tool_rename_bw.gif"
	else document.getElementById("icren").src="img/tool_rename.gif";
	// set up toolbar icons mouseover events
	e=document.getElementById("head").getElementsByTagName("img");
	for (var i=0;i<e.length;i++) {
		c=e[i];
		if (c.className!="tbbut") continue;
		if (c.getAttribute("src").match(/_bw\./)) {
			var o=new Object();o.target=c; (o);
			c.onmouseover=c.onmouseout=c.onmousedown=null;
		} else {
			c.onmouseover=tbmouseover;
			c.onmouseout=tbmouseout;
			c.onmousedown=tbmousedown;
			c.onmouseup=tbmouseover;
		}
	}
	if (view=="s") {
		// dis-, enable filmtrip icons
		if (items[curpos-1].title.match(imgCanRotateRe)) {
			document.getElementById("icrtr").src="img/tool_rrotate.gif";
			document.getElementById("icrtl").src="img/tool_lrotate.gif";
		} else {
			document.getElementById("icrtr").src="img/tool_rrotate_bw.gif";
			document.getElementById("icrtl").src="img/tool_lrotate_bw.gif";
		}
		// set up filmstrip icons mouseover events
		e=document.getElementById("filmnav").getElementsByTagName("img");
		for (var i=0;i<e.length;i++) {
			c=e[i];
			if (c.className!="fibut") continue;
			if (c.getAttribute("src").match(/_bw\./)) {
				var o=new Object();o.target=c;fimouseout(o);
				c.onmouseup=c.onmouseover=c.onmouseout=c.onmousedown=null;
			} else {
				c.onmouseover=fimouseover;
				c.onmouseout=fimouseout;
				c.onmousedown=fimousedown;
				c.onmouseup=fimouseover;
			}
		}
	}
}
function tbmouseover (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px outset #FFFFFF";
	c.style.background="#FAF9F4";
	c.style.top="0px";
	c.style.left="0px";
}
function tbmouseout (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px solid #EBEADB";
	c.style.background="#EBEADB";
	c.style.top="0px";
	c.style.left="0px";
}
function tbmousedown (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px inset #FFFFFF";
	c.style.background="#F9F9F5";
	c.style.top="2px";
	c.style.left="2px";
}
function fimouseover (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px outset #FFFFFF";
	c.style.background="#F3F3ED";
	c.style.top="0px";
	c.style.left="0px";
}
function fimouseout (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px solid #EEF2FB";
	c.style.background="#EEF2FB";
	c.style.top="0px";
	c.style.left="0px";
}
function fimousedown (e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	c.style.border="1px inset #FFFFFF";
	c.style.background="#E7E6E0";
	c.style.top="2px";
	c.style.left="2px";
}	
function update_cut() {// update file icons (e.g. cut / not cut)
	var i;
	if (cb_path==curdir) {
		var a=cb_files.split(':').sort();
		for (i=0;i<items.length;i++)
			if (cb_action==2 && a.binarySearch(items[i].title)>=0) items[i].getElementsByTagName("img")[0].className="cut";
			else items[i].getElementsByTagName("img")[0].className="";
	}
}
function getselection() {
	var s="";
	if (sel)
		for (var i=0;i<items.length;i++)
			if (items[i].sxsel) s+=(s?":":"")+items[i].title;
	return s;
}
function ac_chmod() {
	var p="",f;
	if (sel==0) return;
	for (var i=0;i<items.length;i++) if (items[i].sxsel && (f=items[i].getAttribute("p").substr(-3))) break;
	while ((p=prompt("Please specify the new permissions as a three digit octal number (000-777)",to_octal(f))) && !p.match(/^[0-7][0-7][0-7]$/)) {}
	if (p) {
		document.f.arg.value=p+":"+getselection();
		document.f.action.value="chmod";
		subfrm();
	}
}
function ac_mkdir() {
	var i;
	i=prompt ("New Folder Name","New Folder");
	if (i && !i.match(dotre)) {
		document.f.action.value="mkdir";
		document.f.arg.value=i;
		subfrm();
	}
	docfocus();
}
function ac_command() {
	if (!document.f.command.value || document.f.command.value=="type command here") return;
	document.f.action.value="command";
	document.f.arg.value=document.f.command.value;
	subfrm();
}
function ac_delete() {
	var msg;
	if (!(document.f.arg.value=getselection())) return;
	if (sel>1) msg="Are you sure you want to delete these "+sel+" items?"
	else msg="Are you sure you want to delete '"+document.f.arg.value+"'?"
	if (confirm(msg)) {
		for (var i=0;i<items.length;i++) if (items[i].sxsel) {document.f.curpos.value=i+1;break;}
		document.f.action.value="delete";
		subfrm();
	}
}
function ac_sall() {
	selectAll(true);update_tb();clearSelection();}
function ac_dsall() {
	for (var i=0;i<items.length;i++) {if (items[i].sxsel) deselect (i); else select(i);}
	update_tb();}
function ac_copy(cut) {
	if (!(document.f.cb_files.value=cb_files=getselection())) return;
	document.f.cb_path.value=cb_path=curdir;
	document.f.cb_action.value=cb_action=cut?2:1;
	update_tb();
	update_cut();
}
function ac_paste() {
	if (!cb_files) return;
	var x,i,a=cb_files.split(':');
	// is destination a subdirectory of source?
	for (i=0;i<a.length;i++) {
		x=cb_path+"/"+a[i];
		if (x==curdir || curdir.substr(0,x.length+1)==x+"/") {
			alert("Cannot copy '"+a[i]+"': The destination folder is the same as the source folder");return false;}
	}
	// are we overwriting directories?
	if (cb_path!=curdir) {
		for (i=0;i<items.length;i++) if (a.binarySearch(items[i].title)>=0) break;
		if (i<items.length && !confirm(
			"This folder already contains a file or folder named '"+items[i].title+"'.\n"+
			"Would you like to overwrite the existing file or folder?\n"+
			"Please note: When overwriting an existing folder, only files that have the same\n"+
			"name in the existing folder and in the folder you are moving or copying will be\n"+
			"replaced.")) return false;
	}
	document.f.action.value="paste";
	subfrm();
}
function ac_rename() {
	if (items[curpos-1].title=="..") return;
	var f=document.f.arg;
	var g=items[curpos-1].lastChild;
	var pos=findPos(g);
	f.style.left=(pos[0])+"px";
	f.style.top=(pos[1]-3)+"px";
	f.value=items[curpos-1].title;
	f.style.display="block";
	f.select();
	f.focus();
	f.onblur=do_rename;
}
function do_rename() {
	var i;
	ca_rename();
	if (document.f.arg.value && !document.f.arg.value.match(dotre) && document.f.arg.value!=items[curpos-1].title) {
		var re=new RegExp("[\\\\/:\\*\\?\"><\\|]");
		if (document.f.arg.value.match(re)) {
			alert("A file name cannot contain any of the following characters:\n \\ / : * ? \" < > |");return;}
		// do we have file with the same name?
		for (i=0;i<items.length;i++)
			if (items[i].title==document.f.arg.value && i!=curpos-1) {
				alert("Cannot rename "+items[curpos-1].title+": A file with the name you specified already exists. Specify a different file name.");return;}
		document.f.arg.value=items[curpos-1].title+":"+document.f.arg.value;
		document.f.action.value="rename";
		subfrm();
	}
}
function ca_rename() {
	var f=document.f.arg;
	f.onblur=null;
	f.style.display="none";
	docfocus();
}
function docfocus() {
	document.getElementById("a").focus();}
function goTo(u) {
	document.f.curdir.value=u;
	document.f.action.value="";
	subfrm();
}
function subfrm() {
	if (document.f.cb_files.value || document.f.action.value) document.f.method="post";
	document.f.submit();
}
function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft;
		curtop = obj.offsetTop;
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
			if (obj.id=="filmdiv") curleft-=obj.scrollLeft;
		}
	}
	return [curleft,curtop];
}
function createXmlHttpRequestObject() {
	var xmlHttp;
	try {xmlHttp = new XMLHttpRequest();}
	catch(e){
		var XmlHttpVersions = new Array("MSXML2.XMLHTTP.6.0","MSXML2.XMLHTTP.5.0","MSXML2.XMLHTTP.4.0",
			"MSXML2.XMLHTTP.3.0","MSXML2.XMLHTTP","Microsoft.XMLHTTP");
		for (var i=0; i<XmlHttpVersions.length && !xmlHttp; i++) {
			try {xmlHttp = new ActiveXObject(XmlHttpVersions[i]);}
			catch (e) {}
		}
	}
	if (!xmlHttp) alert("Error creating the XMLHttpRequest object.");
	else return xmlHttp;
}
function ac_about() {
	acdiv=3;
	document.getElementById("back").style.display="block";
	document.getElementById("f_about").style.display="block";}
function ca_about() {
	document.getElementById("f_about").style.display="none";
	document.getElementById("back").style.display="none";
	docfocus();acdiv=0;}	
function ac_upload() {
	acdiv=1;
	document.getElementById("back").style.display="block";
	document.getElementById("f_upload").style.display="block";
	document.u.file1.focus();}
function ca_prefs() {
	if (!firststart) {
		document.getElementById("f_prefs").style.display="none";
		document.getElementById("back").style.display="none";
		docfocus();acdiv=0;
	}
}
function ca_upload() {
	document.getElementById("f_upload").style.display="none";
	document.getElementById("back").style.display="none";
	docfocus();acdiv=0;}
function addUpload() {
	var d=document.getElementById("ulfiles");
	var ul=d.childNodes[d.childNodes.length-1].cloneNode(true);
	var a=ul.firstChild.name.match(/^file(\d+)$/);
	var ue=ul.firstChild;
	var uc=ul.lastChild.firstChild;
	ue.name="file"+(parseInt(a[1])+1);
	ue.value="";
	ul.lastChild.style.display="none";
	uc.name="uz"+(parseInt(a[1])+1);
	uc.checked=false;
	d.appendChild(ul);
}
function do_upload() {
	var i,d=document.u;
	for(i=1;i<999999;i++) if (!d.elements["file"+i] || d.elements["file"+i].value) break;
	if (d.elements["file"+i]) {document.getElementById("upspin").style.display="block";d.submit();}
	else ca_upload();
}
function dethover(e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	while (c.tagName!="TH") c=c.parentNode;
	c.style.background="#FAF9F4";
}
function detout(e) {
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	while (c.tagName!="TH") c=c.parentNode;
	c.style.background="#EBEADB";
}
function sortnew(e) {
	var a,so='d';
	if (!e) var e = window.event;
	var c=e.srcElement?e.srcElement:e.target;
	while (c.tagName!="TH") c=c.parentNode;
	var o=document.f.order.value;
	var no=c.innerHTML.substr(0,1).toLowerCase();
	if (a=o.match(new RegExp("^"+no+"(.)"))) so=a[1];
	so=so=='a'?'d':'a'; // invert sort order
	no=no+so+o.replace(new RegExp(no+"(.)"),'');
	document.f.order.value=no.substr(0,6);
	subfrm();
}
function ac_download() {
	if (!(document.f.arg.value=getselection())) return;
	if (sel>1) msg="Are you sure you want to zip and download these "+sel+" items?"
	else msg="Are you sure you want to zip and download '"+document.f.arg.value+"'?"
	if (confirm(msg)) {
		document.f.action.value="download";
		subfrm();
	}
	docfocus();
}
function ac_logout() {
	document.f.action.value='logout';subfrm();}
function ac_extract() {
	if (!items[curpos-1].title.match(/\.zip$/i)) return;
	if (confirm("Are you sure you want to extract '"+items[curpos-1].title+"' to the current folder?")) {
		document.f.arg.value=items[curpos-1].title;
		document.f.action.value="extract";
		subfrm();
	}
}

function to_octal(n){
	var r='',s=true;
	for(var i=33;i>0;){
		i-=3;
		var o=(n>>i)&0x7;
		if(!s||o!=0){
			s=false;
			r+=''+o;}
	}
	return ("000"+r).substr(r.length);
}
var s_last=0;	// time of last search
var s_strg='';	// search history
function ac_search(k) {
	var c=new Date().valueOf(),i,d;	// what are we searching for?
	if (items[curpos-1].title.toUpperCase().indexOf(k)==0 && (s_strg.length<=1 || c-s_last>900)) {s_strg=k;d=curpos;}
	else if (c-s_last>900) {s_strg=k;d=curpos-1;}
	else {s_strg+=k;d=curpos-1;}
	s_last=c;
	i=d;	// now search
	do {
		if (i>=items.length) i=0;
		if (items[i].title.toUpperCase().indexOf(s_strg)==0) {
			if (i!=curpos-1) doSelection(i+1,0,0,0);
			break;
		}
	} while (++i!=d);
}
// methods for preferences handling
var xmlHttp = createXmlHttpRequestObject();
function ac_prefs() {
	acdiv=2;
	document.getElementById("back").style.display="block";
	document.getElementById("f_prefs").style.display="block";
	document.getElementById("prefs").focus();
	if (!prefsloaded && (xmlHttp.readyState == 4 || xmlHttp.readyState == 0)) {
		xmlHttp.open("GET", "prefs.php?view="+view+"&curdir="+curdirenc+"&order="+order, true);
		xmlHttp.onreadystatechange = ac_prefs1;
		xmlHttp.send(null);
	}
}
function ac_prefs1() {
	if (xmlHttp.readyState == 4) {
		var x=xmlHttp.responseText;
		if (firststart) x="<center><div class=notice>Starting up for the first time - please adjust the configuration if necessary.</div></center>"+x;
		document.getElementById('prefs').innerHTML=x;
		if (firststart) document.prefform.Cancel.style.display='none';
		setupAllTabs();
		init_prefs();
		document.getElementById("prefs").focus();
		prefsloaded=true;
	}
}
function ac_clearcache() {
	document.getElementById("ccbut").value="Clearing cache ...";
	if (xmlHttp.readyState == 4 || xmlHttp.readyState == 0) {
		xmlHttp.open("GET", "index.php?action=clearcache", true);
		xmlHttp.onreadystatechange = ac_clearcache1;
		xmlHttp.send(null);
	}
}
function ac_clearcache1() {
	if (xmlHttp.readyState == 4) {
		var e=document.getElementById("ccbut");
		e.value="Clear cache";
		e.disabled=true;
	}
}
function init_prefs() {
	var i,e=document.getElementById("prefs").getElementsByTagName("input");
	for (i=0;i<e.length;i++) {
		if (e[i].className=="pkey") {
			e[i].onkeydown=ignore;
			e[i].onkeyup=prefs_key;
			e[i].onkeypress=ignore;
		}
	}
}
function ck_prefs() {
	var f=document.prefform;
	var warnings=new Array();
	var errors= new Array();

	// do we have any ports open?
	if (!f.elements["cfg[root_path]"].value) f.elements["cfg[root_path]"].value="/";
	if (isNaN(parseInt(f.elements["cfg[thumb_max_x]"].value)) ||
		isNaN(parseInt(f.elements["cfg[thumb_max_y]"].value)))
		errors.push("Thumbnail dimensions must be a numerical value between 48 and 200.");
	else {
		f.elements["cfg[thumb_max_x]"].value=parseInt(f.elements["cfg[thumb_max_x]"].value);
		f.elements["cfg[thumb_max_y]"].value=parseInt(f.elements["cfg[thumb_max_y]"].value);}
	if (parseInt(f.elements["cfg[thumb_max_x]"].value)<48) f.elements["cfg[thumb_max_x]"].value=48;
	if (parseInt(f.elements["cfg[thumb_max_x]"].value)>200) f.elements["cfg[thumb_max_x]"].value=200;
	if (parseInt(f.elements["cfg[thumb_max_y]"].value)<48) f.elements["cfg[thumb_max_y]"].value=48;
	if (parseInt(f.elements["cfg[thumb_max_y]"].value)>200) f.elements["cfg[thumb_max_y]"].value=200;
	if (f.elements["pass1"].value != f.elements["pass2"].value)
		errors.push("Passwords do not match. Please re-enter.");
	if (!f.elements["cfg[user]"].value) warnings.push("You did not assign a login username - SiteXplorer will be accessible by everyone.");
	if (errors.length) {
		alert ("ERRORS occurred while validating your input:\n\n"+errors.join("\n"));
		return false;
	}
	if (!warnings.length || confirm("WARNING!\n"+warnings.join("\n")+
		"\n\nWould you still like to save your changes?")) {
		if (f.elements["pass1"].value) f.elements["cfg[pass]"].value=f.elements["pass1"].value;
		ca_prefs();
		return true;
	} else return false;
}
function ignore(e) {
	if (!e) var e = window.event;
	if (e.keyCode==13 || e.keyCode==9 || e.keyCode==27) return true; // return, tab and escape are needed for keyboard navigation
	if (e.stopPropagation) e.stopPropagation(true);e.cancelBubble=true;
	return false;}
function prefs_key(e) {
	if (!e) var e = window.event;
	var k=e.keyCode,s="",i;
	if (k==13 || k==9 || k==27) return true; // return, tab and escape are needed for keyboard navigation
	if (e.stopPropagation) e.stopPropagation(true);e.cancelBubble=true;
	// sort out some fixed keys
	if ((k>=33 && k<=40 && !e.altKey) || k==32) return false;
	var c=e.srcElement?e.srcElement:e.target;
	if (!ks[k]) return false;
	if (e.ctrlKey) s+="Ctrl-";
	if (e.altKey) s+="Alt-";
	if (e.shiftKey) s+="Shift-";
	s+=ks[k];
	// check if this key is assigned
	var d=document.getElementById("prefs").getElementsByTagName("input");
	for (i=0;i<d.length;i++) if (d[i].className=="pkey" && d[i].value==s) d[i].value="";
	c.value=s;
	return false;
}

// Functions for the tabbed panes
// This function is used to define if the browser supports the needed
// features
function hasSupport() {

	if (typeof hasSupport.support != "undefined")
		return hasSupport.support;
	
	var ie55 = /msie 5\.[56789]/i.test( navigator.userAgent );
	
	hasSupport.support = ( typeof document.implementation != "undefined" &&
			document.implementation.hasFeature( "html", "1.0" ) || ie55 )
			
	// IE55 has a serious DOM1 bug... Patch it!
	if ( ie55 ) {
		document._getElementsByTagName = document.getElementsByTagName;
		document.getElementsByTagName = function ( sTagName ) {
			if ( sTagName == "*" )
				return document.all;
			else
				return document._getElementsByTagName( sTagName );
		};
	}

	return hasSupport.support;
}

///////////////////////////////////////////////////////////////////////////////////
// The constructor for tab panes
//
// el : HTMLElement		The html element used to represent the tab pane
// bUseCookie : Boolean	Optional. Default is true. Used to determine whether to us
//						persistance using cookies or not
//
function WebFXTabPane( el, bUseCookie ) {
	if ( !hasSupport() || el == null ) return;
	
	this.element = el;
	this.element.tabPane = this;
	this.pages = [];
	this.selectedIndex = null;
	this.useCookie = bUseCookie != null ? bUseCookie : true;
	
	// add class name tag to class name
	this.element.className = this.classNameTag + " " + this.element.className;
	
	// add tab row
	this.tabRow = document.createElement( "div" );
	this.tabRow.className = "tab-row";
	el.insertBefore( this.tabRow, el.firstChild );

	var tabIndex = 0;
	if ( this.useCookie ) {
		tabIndex = Number( WebFXTabPane.getCookie( "webfxtab_" + this.element.id ) );
		if ( isNaN( tabIndex ) )
			tabIndex = 0;
	}
	this.selectedIndex = tabIndex;
	
	// loop through child nodes and add them
	var cs = el.childNodes;
	var n;
	for (var i = 0; i < cs.length; i++) {
		if (cs[i].nodeType == 1 && cs[i].className == "tab-page") {
			this.addTabPage( cs[i] );
		}
	}
}

WebFXTabPane.prototype.classNameTag = "dynamic-tab-pane-control";

WebFXTabPane.prototype.setSelectedIndex = function ( n ) {
	if (this.selectedIndex != n) {
		if (this.selectedIndex != null && this.pages[ this.selectedIndex ] != null )
			this.pages[ this.selectedIndex ].hide();
		this.selectedIndex = n;
		this.pages[ this.selectedIndex ].show();
		
		if ( this.useCookie )
			WebFXTabPane.setCookie( "webfxtab_" + this.element.id, n );	// session cookie
	}
};
	
WebFXTabPane.prototype.getSelectedIndex = function () {
	return this.selectedIndex;
};
	
WebFXTabPane.prototype.addTabPage = function ( oElement ) {
	if ( !hasSupport() ) return;
	
	if ( oElement.tabPage == this )	// already added
		return oElement.tabPage;

	var n = this.pages.length;
	var tp = this.pages[n] = new WebFXTabPage( oElement, this, n );
	tp.tabPane = this;
	
	// move the tab out of the box
	this.tabRow.appendChild( tp.tab );
			
	if ( n == this.selectedIndex )
		tp.show();
	else
		tp.hide();
		
	return tp;
};
	
WebFXTabPane.prototype.dispose = function () {
	this.element.tabPane = null;
	this.element = null;		
	this.tabRow = null;
	
	for (var i = 0; i < this.pages.length; i++) {
		this.pages[i].dispose();
		this.pages[i] = null;
	}
	this.pages = null;
};

// Cookie handling
WebFXTabPane.setCookie = function ( sName, sValue, nDays ) {
	var expires = "";
	if ( nDays ) {
		var d = new Date();
		d.setTime( d.getTime() + nDays * 24 * 60 * 60 * 1000 );
		expires = "; expires=" + d.toGMTString();
	}

	document.cookie = sName + "=" + sValue + expires + "; path=/";
};

WebFXTabPane.getCookie = function (sName) {
	var re = new RegExp( "(\;|^)[^;]*(" + sName + ")\=([^;]*)(;|$)" );
	var res = re.exec( document.cookie );
	return res != null ? res[3] : null;
};

WebFXTabPane.removeCookie = function ( name ) {
	setCookie( name, "", -1 );
};

///////////////////////////////////////////////////////////////////////////////////
// The constructor for tab pages. This one should not be used.
// Use WebFXTabPage.addTabPage instead
//
// el : HTMLElement			The html element used to represent the tab pane
// tabPane : WebFXTabPane	The parent tab pane
// nindex :	Number			The index of the page in the parent pane page array
//
function WebFXTabPage( el, tabPane, nIndex ) {
	if ( !hasSupport() || el == null ) return;
	
	this.element = el;
	this.element.tabPage = this;
	this.index = nIndex;
	
	var cs = el.childNodes;
	for (var i = 0; i < cs.length; i++) {
		if (cs[i].nodeType == 1 && cs[i].className == "tab") {
			this.tab = cs[i];
			break;
		}
	}
	
	// insert a tag around content to support keyboard navigation
	
	
	var a = document.createElement( "A" );
	this.aElement = a;
	a.href = "#";
	a.onclick = function () { return false; };
	while ( this.tab.hasChildNodes() )
		a.appendChild( this.tab.firstChild );
	this.tab.appendChild( a );

	
	// hook up events, using DOM0
	var oThis = this;
	this.tab.onclick = function () { oThis.select(); };
	this.tab.onmouseover = function () { WebFXTabPage.tabOver( oThis ); };
	this.tab.onmouseout = function () { WebFXTabPage.tabOut( oThis ); };
}

WebFXTabPage.prototype.show = function () {
	var el = this.tab;
	var s = el.className + " selected";
	s = s.replace(/ +/g, " ");
	el.className = s;
	
	this.element.style.display = "block";
};

WebFXTabPage.prototype.hide = function () {
	var el = this.tab;
	var s = el.className;
	s = s.replace(/ selected/g, "");
	el.className = s;

	this.element.style.display = "none";
};
	
WebFXTabPage.prototype.select = function () {
	this.tabPane.setSelectedIndex( this.index );
};
	
WebFXTabPage.prototype.dispose = function () {
	this.aElement.onclick = null;
	this.aElement = null;
	this.element.tabPage = null;
	this.tab.onclick = null;
	this.tab.onmouseover = null;
	this.tab.onmouseout = null;
	this.tab = null;
	this.tabPane = null;
	this.element = null;
};

WebFXTabPage.tabOver = function ( tabpage ) {
	var el = tabpage.tab;
	var s = el.className + " hover";
	s = s.replace(/ +/g, " ");
	el.className = s;
};

WebFXTabPage.tabOut = function ( tabpage ) {
	var el = tabpage.tab;
	var s = el.className;
	s = s.replace(/ hover/g, "");
	el.className = s;
};


// This function initializes all uninitialized tab panes and tab pages
function setupAllTabs() {
	if ( !hasSupport() ) return;

	var all = document.getElementsByTagName( "div" );
	var l = all.length;
	var tabPaneRe = /tab\-pane/;
	var tabPageRe = /tab\-page/;
	var cn, el;
	var parentTabPane;
	
	for ( var i = 0; i < l; i++ ) {
		el = all[i]
		cn = el.className;

		// no className
		if ( cn == "" ) continue;
		
		// uninitiated tab pane
		if ( tabPaneRe.test( cn ) && !el.tabPane )
			new WebFXTabPane( el );
	
		// unitiated tab page wit a valid tab pane parent
		else if ( tabPageRe.test( cn ) && !el.tabPage &&
					tabPaneRe.test( el.parentNode.className ) ) {
			el.parentNode.tabPane.addTabPage( el );			
		}
	}
}

function disposeAllTabs() {
	if ( !hasSupport() ) return;
	
	var all = document.getElementsByTagName( "*" );
	var l = all.length;
	var tabPaneRe = /tab\-pane/;
	var cn, el;
	var tabPanes = [];
	
	for ( var i = 0; i < l; i++ ) {
		el = all[i]
		cn = el.className;

		// no className
		if ( cn == "" ) continue;
		
		// tab pane
		if ( tabPaneRe.test( cn ) && el.tabPane )
			tabPanes[tabPanes.length] = el.tabPane;
	}
	
	for (var i = tabPanes.length - 1; i >= 0; i--) {
		tabPanes[i].dispose();
		tabPanes[i] = null;
	}
}