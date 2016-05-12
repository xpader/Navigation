<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<title><?=$title?></title>
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<style type="text/css">
html,body {overflow:hidden; width:100%; height:100%; margin:0; background-color:#F7F7F7;}
body,td,th {font-size:14px; font-family:Verdana,Arial,微软雅黑,宋体;}
a {text-decoration:none; color:#0000FF;}
table {border-collapse:collapse; border-spacing:0; empty-cells:show; clear:both;}
table,ul,li {padding:0; margin:0;}
img {border:none;}
.button {
	color:#CB760D; background-color:#FFD361; border:1px solid #E78A18; border-radius:3px; text-decoration:none;
	box-shadow:0 1px 2px rgba(0,0,0,.2);
	background: -webkit-gradient(linear, left top, left bottom, from(#FFDA73), to(#FFB039));
	background: -moz-linear-gradient(top, #FFDA73, #FFB039);
	background: -o-linear-gradient(top, #FFDA73, #FFB039);
	background: gradient(top, #FFDA73, #FFB039);
	filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#FFDA73', EndColorStr='#FFB039');
}
a.button {color:#CB760D;}
.button:active {
	background-color:#FFB742;
	background: -webkit-gradient(linear, left top, left bottom, from(#F2A531), to(#FFC66C));
	background: -moz-linear-gradient(top, #F2A531, #FFC66C);
	background: -o-linear-gradient(top, #F2A531, #FFC66C);
	background: gradient(top, #F2A531, #FFC66C);
	filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#F2A531', EndColorStr='#FFC66C');
}
.cc:after {content:"."; display:block; height:0; clear:both; visibility:hidden;}
.cc {zoom:1;}

.addressBar {border-bottom:1px solid #D6E579; background-color:#F2FFA5;}
.top {background-color:#F7FFC9; border-bottom:2px solid #D6E579; width:100%; position:absolute; top:0; right:16px; z-index:1;}
.top .search {float:right; margin-right:5px;}
.top .toolbar {padding:3px 10px 3px 22px;}
.top .nav {line-height:14px;}
.top .nav a {margin-left:10px; padding:2px 5px; display:block; height:16px; float:left;}
.top .nav img {vertical-align:top;}
.top .nav span {line-height:20px;}
#address {padding-left:22px;}
#address a, #address em {float:left; display:block; border:1px solid #F2FFA5; border-top:none; border-bottom:none; height:26px; line-height:26px;}
#address a:hover, #address em:hover {color:#000; border-color:#D6E579;}
#address a {text-decoration:none; color:#666; float:left; padding:0 3px;}
#address em {width:14px; background:url(/explorer/image/sarrow.gif) center 9px no-repeat; position:relative;}
#address em.current {background-position:center -15px; border-color:#D6E579;}
#subdir {position:absolute; z-index:2; border:1px solid #CEDE6F; width:auto; display:inline-block; background-color:#F2FFA5; left:-1px; top:26px; padding:5px; list-style:none; overflow-y:auto; box-shadow:0 3px 3px rgba(0,0,0,.3);}
#subdir li {background:url(/explorer/image/dir.gif) left center no-repeat; text-indent:23px; font-style:normal; border-bottom:1px solid #D8E87E;  padding-right:5px;}
#subdir li.current {font-weight:bold;}
#subdir li a {float:none; border:none; white-space:nowrap; padding:0;}
#subdir li a:hover {}
.viewmode {float:right; border:1px solid #CCC; height:14px; background-color:#EAFF6A; font-size:1px; margin-top:3px;}
.viewmode a {display:block; position:relative; overflow:hidden; background-image:url(/explorer/image/viewmode.gif); background-repeat:no-repeat; width:15px; height:14px; float:left;}
.viewmode-icon {background-position:-14px 3px; border-right:1px solid #CCC;}
.viewmode-list {background-position:2px 3px;}
.viewmode .current {background-color:#FFF;}
#virtualBody {width:100%; height:100%; overflow-y:scroll; overflow-x:auto;}
#vbodyFix {margin-top:56px;}
.rightGoTop{width:90%; text-align:right; clear:left;}
address {text-align:right;}
.padContent {padding:10px;}
.list {_width:98%;}
.list table tr {height:22px;}
.list table th a {color:#FFF; display:inline-block; height:17px; line-height:17px;}
.sort, .sort-asc, .sort-desc {background:url(/explorer/image/sort.gif) no-repeat right 2px; padding-right:17px;}
.sort-asc {background-position:right -14px;}
.sort-desc {background-position:right -25px;}
.folder span a, #filelist a {text-decoration:underline;}
.list .folder a:visited, .list tbody a:visited {color:#810081;}
.folder a:hover {background-color:#F2FFA5; font-style:italic; font-weight:bold; color:black;}
.folder {width:138px; height:90px; text-align:center; border:1px solid #F5F583; background-color:#FFE5E5; float:left; margin:5px; overflow:hidden; text-overflow:ellipsis; word-break:break-all;}
.folder img{margin-top:10px;margin-bottom:5px;}
#filelist a {text-decoration:underline;}
#filelist em a {text-decoration:none;}
#filelist a:visited {color:#810081;}
#filelist td {padding:5px;}
</style>
<script type="text/javascript">
Array.prototype.each = function(callback) {
	for(var i=0,o;o=this[i]; i++) {
		if (callback.call(o,(this),i) === false) {
			break;
		}
	}
};

var is_ie = (navigator.userAgent.indexOf('MSIE') != -1);
var is_gecko = (navigator.product == "Gecko");
var is_ns = (document.layers);
var is_w3 = (document.getElementById && !is_ie);
var searchIndex = 0;

var $ = function(id){ return document.getElementById(id); }

function ToFindInPage() {
	if ($('schstring').value!='') {
		FindInPage($('schstring').value);
		$('schbt').disabled = false;
	} else {
		$('schbt').disabled = true;
	}
}

function FindInPage(str) {
	if(!str) {
		alert('未找到指定内容');
		return;
	}
	if (is_w3 || is_ns) {
		if (!window.find(str)) {
			alert('到达页尾，从页首继续');
			while (1) {
				if (window.find(str,false,true)) break;
			}
			return;
		}
	} else if (is_ie) {
		var found;
		var txt = document.body.createTextRange();
		for (var i = 0; i <= searchIndex && (found = txt.findText(str)) != false; i++) {
			txt.moveStart('character',1);
			txt.moveEnd('textedit');
		}
		if (found) {
			searchIndex++;
			txt.moveStart('character',-1);
			txt.findText(str);
			try {
				txt.select();
				txt.scrollIntoView();
			} catch(e) { FindInPage(str); }
		} else {
			if (searchIndex > 0) {
				searchIndex = 0;
				alert('到达页尾，从页首继续');
				FindInPage(str);
			} else {
				alert('未找到指定内容');
			}
		}
	}
}

function setBackgroundColorHover(id,tag,color) {
	var elements = document.getElementById(id).getElementsByTagName(tag);
	for (var i=0,row; row=elements[i]; i++) {
		if (typeof arguments[3] != 'undefined' && i % 2 == 1) {
			row.style.backgroundColor = arguments[3];
		}
		if (color) {
			row.onmouseover = function(){
				if (!this.origColor) this.origColor = this.style.backgroundColor;
				this.style.backgroundColor = color;
			};
			row.onmouseout = function(){ this.style.backgroundColor = this.origColor; }
		}
	}
}

function pLoadScripts(scripts,callback) {
	if(typeof(scripts)!="object"){scripts=[scripts];}var HEAD=document.getElementsByTagName("head").item(0)||document.documentElement,
		s=[],loaded=0;for(var i=0;i<scripts.length;i++){s[i]=document.createElement("script");s[i].setAttribute("type","text/javascript");
		s[i].onload=s[i].onreadystatechange=function(){if(!this.readyState||/loaded|complete/.test(this.readyState)){loaded++;
			this.onload=this.onreadystatechange=null;this.parentNode.removeChild(this);if(loaded==scripts.length&&typeof(callback)=="function")
			{callback();}}};s[i].setAttribute("src",scripts[i]);HEAD.appendChild(s[i]);}
}

function removeElement(element) {
	if (element) element.parentNode.removeChild(element);
}

function getElementsByClass(searchClass,node,tag) {
	var classElements = [];
	if (node == null) node = document;
	if (tag == null) tag = '*';
	var els = node.getElementsByTagName(tag);
	var elsLen = els.length;
	var pattern = new RegExp("(^|\\s)" + searchClass + "(\\s|$)");
	for (var i=0,j=0; i<elsLen; i++) {
		if (pattern.test(els[i].className)) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

function each(elements,callback) {
	for (var i=0,a; a=elements[i]; i++) {
		callback.call(a,i);
	}
}

if (is_ie) {
	document.execCommand("BackgroundImageCache", false, true);
}
</script>
</head>
<body>
<a name="Top"></a>

<div class="top">
	<div class="addressBar cc">
		<div class="search"><input type="text" name="schstring" id="schstring" style="width:150px;" onfocus="document.getElementById('schbt').disabled = false;" onclick="this.select();"  onkeydown="if(event.keyCode==13) { ToFindInPage(); }" /> <input type="button" id="schbt" value="查找" onclick="ToFindInPage();" /></div>
		<div id="address" class="cc"><a href="?dir=."><b>根目录</b></a><em></em><?=$breadCrumb?></div>
	</div>
	<div class="toolbar cc">
		<div class="viewmode cc">
			<a href="?dir=<?=$current?>&viewmode=icon" class="viewmode-icon<?php if ($viewMode == 'icon') { ?> current<?php } ?>" title="图标模式">&nbsp;</a>
			<a href="?dir=<?=$current?>&viewmode=list" class="viewmode-list<?php if ($viewMode == 'list') { ?> current<?php } ?>" title="列表模式">&nbsp;</a>
		</div>
		<div class="nav">
			<?php if ($dir != '') { ?>
				<a href="?dir=." class="button"><img src="/explorer/image/dir.gif" /> ROOT</a>
				<a href="?dir=<?=$upDir?>" class="button"><img src="/explorer/image/up.gif" /> 上一层</a>
			<?php } ?>
			<a href="javascript:location.reload();" class="button"><img src="/explorer/image/round.gif" /> 刷新</a>
			<span>&nbsp;　&nbsp;<?=$status?></span>
		</div>
	</div>
</div>

<div id="virtualBody">
	<div id="vbodyFix">

		<div class="padContent">
			<div class="list">
				<?php if ($viewMode == 'icon' && $countDir > 0) { foreach($listDir as $row) { ?>
					<div class="folder"><a href="?dir=<?=$dir.$row['uri']?>" title="浏览此目录"><img src="/explorer/image/folder.gif" /><br /><span><?=$row['filename']?></span></a><br />&nbsp;</div>
				<?php } }
				if (($viewMode == 'icon' && $countFile > 0) || ($viewMode == 'list' && $countFile + $countDir > 0)) {
					$sortNames = array('filename', 'filemtime', 'type', 'filesize');
					$sorts = array();
					if (!$sort) {
						foreach ($sortNames as $row) {
							$sorts[$row] = array("$row,asc", 'sort');
						}
					} else {
						foreach ($sortNames as $row) {
							if ($sortField == $row) {
								$orderBy = $sortMethod == 'asc' ? "$row,desc" : ($sortMethod == 'desc' ? 'no' : "$row,asc");
								$sorts[$row] = array($orderBy, "sort-$sortMethod");
							} else {
								$sorts[$row] = array("$row,asc", '');
							}
						}
					}
					?>
					<table width="100%" cellpadding="1" cellspacing="0" border="0">
						<thead>
						<tr style="background:#E8E8A0 url('/explorer/image/th.gif') repeat-x;height:26px;">
							<th width="24">&nbsp;</th>
							<th align="left">&nbsp;<a href="?dir=<?=$current?>&sort=<?=$sorts['filename'][0]?>" <?php if ($sorts['filename'][1]) { ?>class="<?=$sorts['filename'][1]?>" <?php } ?>title="按名称排序"><?=($viewMode == 'list' ? '名称' : '文件名')?></a></th>
							<th width="200" align="center"><a href="?dir=<?=$current?>&sort=<?=$sorts['filemtime'][0]?>" <?php if ($sorts['filemtime'][1]) { ?>class="<?=$sorts['filemtime'][1]?>" <?php } ?>title="按最后修改时间排序">最后修改时间</a></th>
							<th width="110" align="left">&nbsp;<a href="?dir=<?=$current?>&sort=<?=$sorts['type'][0]?>" <?php if ($sorts['type'][1]) { ?>class="<?=$sorts['type'][1]?>" <?php } ?>title="按文件扩展名排序">类型</a></th>
							<th width="120" align="right"><a href="?dir=<?=$current?>&sort=<?=$sorts['filesize'][0]?>" <?php if ($sorts['filesize'][1]) { ?>class="<?=$sorts['filesize'][1]?>" <?php } ?>title="按文件大小排序">文件大小</a>　</th>
						</tr>
						</thead>
						<tbody id="filelist">
						<?php if ($viewMode == 'list') { foreach ($listDir as $row) { ?>
							<tr align="center">
								<td><img src="/explorer/image/dir.gif" border="0" /></td>
								<td align="left"><a href="?dir=<?=$dir.$row['uri']?>" title="浏览 <?=$row['filename']?> 目录"><?=$row['filename']?></a></td>
								<td><?=date('Y-m-d H:i:s',$row['filemtime'])?></td>
								<td align="left">文件夹</td>
								<td>&nbsp;</td>
							</tr>
						<?php } } foreach($listFile as $row) { ?>
							<tr align="center">
								<td><img src="/explorer/image/<?=$row['icon']?>" border="0" /></td>
								<td align="left"><a href="/explorer/download?file=<?=$dir.rawurlencode($row['uri'])?>" target="_blank" title="访问 <?=$row['filename']?>"><?=$row['filename']?></a></td>
								<td><?=date('Y-m-d H:i:s',$row['filemtime'])?></td>
								<td align="left"><?=strtoupper($row['type'])?> 文件</td>
								<td align="right"><?=$row['filesizeh']?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php } ?>
			</div>
			<div class="rightGoTop"><a href="javascript:void(0);" onclick="document.getElementById('virtualBody').scrollTop=0;">↑Top</a></div>
			<?=$footer?>
		</div>

	</div>
</div>
</body>
<script type="text/javascript">
//$("schstring").focus();

if (document.getElementById("filelist")) { setBackgroundColorHover("filelist","tr","#BFFAFF","#F5F0F0"); }

var symbol = $("address").getElementsByTagName("em"), subdirs = [], listSubDirectorys = function() {
	if (document.getElementById("subdir")) { removeElement($("subdir")); }

	var dir = this.getAttribute("data-addr");
	dir = dir ? dir + "/" : "";

	var nextDir = this.nextSibling == null ? "" : this.nextSibling.innerHTML;

	var sub = document.createElement("ul");
	sub.setAttribute("id", "subdir");

	pLoadScripts("/explorer/subdir?dir=" + dir, function() {
		for (var i=0,row; row=subdirs[i]; i++) {
			var li = document.createElement("li");
			if (row[0] == nextDir) { li.className = "current"; }
			li.innerHTML = '<a href="?dir=' + dir + row[1] + '">' + row[0] + '</a>';
			sub.appendChild(li);
		}

		var adjust = document.documentElement.clientHeight - 50;

		if (sub.offsetHeight > adjust) {
			sub.style.height = adjust + "px";
			sub.style.width = (sub.offsetWidth + 18) + "px";
		}
	});

	this.appendChild(sub);
	this.className = "current";

	document.body.onclick = function(e) {
		var target = (is_ie ? event.srcElement : e.target), outEm = 1;
		getElementsByClass("current",$("address"),"em").each(function(){
			target != this ? this.className = "" : outEm = 0;
		});
		if (outEm && sub != target) {
			removeElement(sub);
			document.body.onclick = null;
		}
	};
};

each(symbol,function(){
	this.onclick = listSubDirectorys;
});
</script>
</html>