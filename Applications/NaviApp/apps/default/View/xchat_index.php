<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no">
<title>XChat</title>
<meta name="description" content="A simple chat room demo on websocket."/>
<link rel="shortcut icon" type="image/x-icon" href="data:image/vnd.microsoft.icon;base64,AAABAAEAEA4AAAEAIADgAwAAFgAAACgAAAAQAAAAHAAAAAEAIAAAAAAAgAMAAPwnAAD8JwAAAAAAAAAAAABubGsAbmxrAG5sayZubGsXbmxrAG5sawAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAbmxrAG5sawBubGtLbmxrom5sa1RubGsZbmxrAW5sawAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABubGsAbmxrEW5sa35ubGtObmxrcW5sa11ubGsobmxrHm5saxRubGsFbmxrAG5sawAAAAAAAAAAAAAAAAAAAAAAbmxrAG5sawBubGtfbmxrd25sax1ubGs5bmxrZW5sa2hubGtvbmxrcG5sa1NubGsZbmxrAG5sawAAAAAAbmxrAG5sawNubGtFbmxrd25sa0JubGsIbmxrAG5sawBubGsAbmxrBG5saxVubGtDbmxrdW5sa0RubGsCbmxrAG5sawBubGtHbmxrZ25saw1ubGsAbmxrAAAAAAAAAAAAAAAAAAAAAABubGsAbmxrAG5saw5ubGtnbmxrR25sawBubGscbmxrcW5saw1ubGsAbmxrAG5sawFubGsAbmxrAG5sawFubGsAbmxrAW5sawBubGsAbmxrDW5sa3FubGsdbmxrR25sa0xubGsAbmxrAG5sawhubGt3bmxrKG5sazZubGttbmxrGG5sa31ubGsYbmxrAG5sawBubGtLbmxrSW5sa05ubGtEbmxrAG5sawBubGsGbmxrWG5sax1ubGsnbmxrUW5saxFubGtdbmxrEW5sawBubGsAbmxrQ25sa1BubGssbmxrZ25sawJubGsAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABubGsAbmxrAm5sa2dubGstbmxrA25sa2JubGtGbmxrAW5sawAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABubGsAbmxrAW5sa0ZubGtibmxrA25sawBubGsObmxraW5sa1xubGsYbmxrAW5sawAAAAAAAAAAAG5sawBubGsBbmxrGW5sa11ubGtobmxrDm5sawAAAAAAbmxrAG5sawdubGtBbmxrcW5sa2hubGtPbmxrQW5sa0FubGtQbmxraG5sa3BubGs/bmxrBm5sawAAAAAAAAAAAAAAAABubGsAbmxrAG5sawhubGsobmxrRm5sa1RubGtUbmxrRW5saydubGsIbmxrAG5sawAAAAAAAAAAAIf/AADB/wAAwB8AAOAHAACDAQAAj/EAABgIAAAwDAAAMAwAAB/4AAAP8AAAg8EAAMADAADwDwAA">
<style type="text/css">
html, body, ul, li {margin:0; padding:0;}
ul, li {list-style:none;}
html, body {height:100%;}
body {background-color:#203656; color:#D5D5D5; font-size:14px; font-family:Helvetica Neue, Helvetica, Arial, PingFang SC, Hiragino Sans GB, WenQuanYi Micro Hei, Microsoft Yahei, sans-serif; overflow:hidden;}
/*::-webkit-scrollbar-track-piece {background-color: rgba(255,255,255,0.2);}*/
::-webkit-scrollbar {width:5px; height:10px;}
::-webkit-scrollbar-thumb {background-color:rgba(110, 135, 171, 0.5); border-radius:6px; background-clip:padding-box; border:none; min-height:28px;}
::-webkit-scrollbar-thumb:hover {background-color:rgb(110, 135, 171);}
* {box-sizing:border-box;}
a {color:#8d89f9;}
a:hover {color:#0c00ff;}
a:visited {color:#807ea9;}
hr {border:none; height:1px; background-color:#5d7698;}

.wrap {position:relative; max-width:750px; min-width:580px; min-height:200px; margin:18px auto; background-color:#2E476B; height:90%; border-radius:5px; overflow:hidden; box-shadow:0 0 18px #1c283a;}
.main {position:absolute; left:0; top:50px; bottom:0; width:100%;}
.main > * {height:100%;}
header {height:50px; background-color:#4a6488; padding:10px; font-size:18px; text-align:center; line-height:30px; border-bottom:1px solid #5f6f88;}

.pop {width:auto; overflow-x:hidden; overflow-y:auto; zoom:1; margin:0; padding-top:15px;}
.pop > li {margin-bottom:15px;}
.pop .tip {text-align:center;}
.pop .tip span {background-color:#395276; color:#7B8AA1; border-radius:5px; padding:2px 5px; font-size:10px; display:inline-block;}
.pop .split {text-align:center; border-bottom:1px solid #566d8e; color:#828282; font-size:10px; margin:0 15px 15px; padding-bottom:3px;}
.pop::-webkit-scrollbar-thumb {background-color:transparent;}
.pop:hover::-webkit-scrollbar-thumb {background-color:rgba(110, 135, 171, 0.5);}
.pop::-webkit-scrollbar-thumb:hover {background-color:rgb(110, 135, 171);}

.message {padding:0 10px;}
.message-head {font-size:12px; color:#657384; margin-bottom:2px;}
.message-nick {margin-right:7px; color:#96a4b7;}
.message-content {padding:8px; border-radius:8px; display:inline-block; position:relative; max-width:90%; word-wrap:break-word;}
.message-content:before {display:block; width:0; height:0; content:"."; font-size:0; border:7px solid #2E476B; position:absolute; z-index:1; top:9px;}
.message-receive .message-content {margin-left:7px; background-color:#416FB2;}
.message-receive .message-content:before {left:-14px; border-right-color:#416FB2;}
.message-send {text-align:right;}
.message-send .message-content {margin-right:7px; background-color:#49658C; text-align:left;}
.message-send .message-content:before {right:-14px; border-left-color:#49658C;}
.message-content img {max-width:100%;}

.recent {float:left; clear:left; width:150px; border-right:1px solid #5f6f88; background-color:#3c587f;  }
.recent ul li {border-bottom: 1px solid #586373; cursor:pointer;}
.recent ul li:hover {background-color:#45638c;}
.recent-nick {padding:5px;  }
.recent-msg {padding:0 5px 5px; font-size:10px; color:#748aa9;}
.area {width:auto; overflow:hidden; zoom:1; position:relative;}
.area-chat {position:absolute; left:0; top:0; width:100%; bottom:50px;}
.area-chat > * {height:100%;}

.online {width:150px; float:right; clear:right; background-color:#3c587f; padding:8px; overflow-y:auto;}
.tech-desc {font-size:12px;}
.tech-desc b {display:block; font-size:14px;}
#onlineList li {cursor:pointer;}
#onlineList li:before {content:"♞"; margin-right:5px; vertical-align:bottom;}

.tool {position:absolute; height:50px; left:0; bottom:0; width:100%; background-color:#FFF;}
.tool textarea {height:100%; width:100%; padding:5px 50px 5px 5px; border:none; border-radius:0; resize:none; background-color:transparent; float:left;}
.tool button {height:46px; width:46px; float:left; margin:2px 0 0 -50px; display:block; background-color:#D3D3D3; -webkit-appearance:none; border-radius:3px; border:none; font-weight:bold; color: #818181;}
.tool-name-reg {position:absolute; z-index:1; left:0; bottom:0; height:100%; width:100%; background-color:#7a92b5;}
.tool-name-reg form {width:200px; margin:15px auto 0;}
.reg-name {width:130px; margin:0; float:left;}
.reg-btn {width:60px; padding:0; margin-left:5px;}

.statusbar {position:absolute; z-index:1; text-align:center; background-color:rgba(243, 212, 126, 0.74); width:100%; bottom:50px; color:#1a2a42; padding:5px 0; display:none;}
#msgSound {display:none;}
</style>
</head>
<body>
<div class="wrap">
	<header>XChat 聊天室演示</header>
	<div class="main">
		<div class="recent">
			<ul>
				<li>
					<div class="recent-nick">德玛西亚</div>
					<div class="recent-msg">创建一个新的定时微...</div>
				</li>
				<li>
					<div class="recent-nick">德玛西亚</div>
					<div class="recent-msg">aaaaaaaaaaaaaaaaaa...</div>
				</li>
				<li>
					<div class="recent-nick">德玛西亚</div>
					<div class="recent-msg">创建一个新的定时微...</div>
				</li>
			</ul>
		</div>
		<div class="area">
			<div class="area-chat">
				<div class="online">
					<div>当前在线：<span id="onlineCount">..</span></div>
					<hr/>
					<ul id="onlineList"></ul>
					<hr/>
					<div id="notiSetting">
						<div style="text-align:center;">消息提醒</div>
						<label><input type="checkbox" name="notiSound" value="1" checked />声音</label>
						<label><input type="checkbox" name="notiTitle" value="1" checked />标题</label><br>
						<label><input type="checkbox" name="notiBrowser" value="1" checked />通知</label>
					</div>
					<hr/>
					<p style="text-align:center;">ping....<span id="lastActive">*</span>....pong</p>
					<hr/>
					<div class="tech-desc">
						<p><b>Websocket</b>Connection protocol</p>
						<p><b>Navigation</b>PHP Web Framework</p>
						<p><b>Workerman</b>PHP Socket Framework</p>
						<p><a href="http://git.oschina.net/pader/Navigation" target="_blank">Git@OSC</a></p>
					</div>
				</div>
				<ul class="pop"></ul>
			</div>
			<div class="statusbar"></div>
			<div class="tool" id="bottomArea">
				<div class="tool-name-reg">
					<p style="text-align:center;">加载中..</p>
					<form method="post" style="display:none;">
						<input type="text" name="mynick" value="" placeholder="输入昵称.." class="reg-name" />
						<input type="submit" value="开始聊天" class="reg-btn" />
					</form>
				</div>
				<textarea id="sendText" placeholder="输入要发送的内容.."></textarea>
				<button type="button" id="sendBtn">发送</button>
			</div>
		</div><!--.area-->
	</div><!--.main-->
</div>

<input type="hidden" id="userHash" value="<?=$hash?>">
<audio controls="controls" id="msgSound">
	<source src="/static/xchat.mp3" type="audio/mpeg">
</audio>
<script src="/static/lib/jquery-2.2.4.js"></script>
<script src="/static/lib/jquery.cookie.js"></script>
<script src="/static/lib/push.min.js"></script>
<script src="/static/xchat.js"></script>
</body>
</html>