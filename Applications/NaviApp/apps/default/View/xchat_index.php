<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>XChat</title>
<meta name="description" content="A simple chat room demo on websocket."/>
<link rel="shortcut icon"type="image/x-icon" href="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7">
<style type="text/css">
html, body, ul, li {margin:0; padding:0;}
ul, li {list-style:none;}
body {background-color:#203656; color:#D5D5D5; font-size:14px; overflow:hidden;}
/*::-webkit-scrollbar-track-piece {background-color: rgba(255,255,255,0.2);}*/
::-webkit-scrollbar {width:5px; height:10px;}
::-webkit-scrollbar-thumb {background-color:rgba(110, 135, 171, 0.5); border-radius:6px; background-clip:padding-box; border:none; min-height:28px;}
::-webkit-scrollbar-thumb:hover {background-color:rgb(110, 135, 171);}
* {box-sizing:border-box;}
a {color:#8d89f9;}
a:hover {color:#0c00ff;}
a:visited {color:#807ea9;}
hr {border:none; height:1px; background-color:#5d7698;}

.wrap {position:relative; max-width:600px; margin:0 auto; background-color:#2E476B;}
.main {position:absolute; left:0; top:0; bottom:50px; width:100%;}
.main > * {height:100%;}

.pop {width:auto; overflow-x:hidden; overflow-y:auto; zoom:1; margin:0; padding-bottom:6px;}
.pop > li {margin-top:6px;}
.pop .tip {text-align:center;}
.pop .tip span {background-color:#375175; color:#8d98a9; border-radius:5px; padding:2px 5px; font-size:10px; display:inline-block;}

.message {padding:0 10px;}
.message-head {font-size:12px; color:#64758c;}
.message-nick {margin-right:5px;}
.message-send {text-align:right;}
.message-send-status {color:#3ac313; margin-left:10px; font-size:10px;}
.message-send-status:before {content:"[";}
.message-send-status:after {content:"]";}

.online {width:150px; float:right; clear:right; background-color:#3c587f; padding:8px; overflow-y:auto;}
.tech-desc {font-size:12px;}
.tech-desc b {display:block; font-size:14px;}
#onlineList li {margin-left:6px; list-style:disc inside;}
.tool {position:absolute; height:50px; left:0; bottom:0; width:100%;}
.tool textarea {height:100%; zoom:1; border:none; padding:5px;}
.tool button {height:100%; width:50px; float:right; clear:right; display:block;}
.tool-name-reg {position:absolute; z-index:1; left:0; bottom:0; height:100%; width:100%; background-color:#7a92b5;}
.tool-name-reg form {width:200px; margin:15px auto 0;}
.reg-name {width:130px; margin:0;}
.reg-btn {width:60px; margin:0; margin-left:5px;}
</style>
</head>
<body>
<div class="wrap">
	<div class="main">
		<div class="online">
			<div>当前在线：<span id="onlineCount">..</span></div>
			<hr/>
			<ul id="onlineList"></ul>
			<hr/>
			<p>Last pong <span id="lastActive">BEGIN</span> seconds ago.</p>
			<hr/>
			<div class="tech-desc">
				<p><b>Websocket</b>Connection used</p>
				<p><b>Ping -- Pong</b>Every 30 seconds</p>
				<p><b>Workerman</b>PHP Socket Framework</p>
			</div>
			<hr/>
			<p><a href="http://git.oschina.net/pader/Navigation" target="_blank">Git@OSC</a></p>
		</div>
		<ul class="pop"></ul>
	</div>
	<div class="tool" id="bottomArea">
		<div class="tool-name-reg">
			<form method="post">
				<input type="text" name="mynick" value="" placeholder="输入昵称.." class="reg-name" autocomplete="off"/>
				<input type="submit" value="开始聊天" class="reg-btn" />
			</form>
		</div>
		<button type="button" id="sendBtn">发送</button>
		<textarea id="sendText" placeholder="输入要发送的内容.."></textarea>
	</div>
</div>

<script src="/static/lib/jquery-2.2.4.js"></script>
<script src="/static/xchat.js"></script>
</body>
</html>