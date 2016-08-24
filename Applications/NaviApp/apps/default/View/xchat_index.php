<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>XChat</title>
<style type="text/css">
html, body, ul, li {margin:0; padding:0;}
ul, li {list-style:none;}
body {background-color:#203656; color:#D5D5D5; font-size:14px; overflow:hidden;}
* {box-sizing:border-box;}
.wrap {position:relative; max-width:600px; margin:0 auto; background-color:#2E476B;}
.main {position:absolute; left:0; top:0; bottom:50px; width:100%;}
.main > * {height:100%;}

.pop {width:auto; overflow-x:hidden; overflow-y:auto; zoom:1; margin:0;}
.pop > li {margin-top:6px;}
.pop .tip {text-align:center;}
.pop .tip span {background-color:#456088; color:#8d98a9; border-radius:5px; padding:2px 5px; font-size:10px; display:inline-block;}

.message {padding:0 10px;}
.message-head {font-size:12px; color:#64758c;}
.message-nick {margin-right:5px;}
.message-send {text-align:right;}
.message-send-status {color:#3ac313; margin-left:10px; font-size:10px;}
.message-send-status:before {content:"[";}
.message-send-status:after {content:"]";}

.online {width:150px; float:right; clear:right; background-color:#3c587f; padding:8px;}

.tool {position:absolute; height:50px; left:0; bottom:0; width:100%;}
.tool textarea {height:100%; width:auto; display:block; zoom:1; overflow:hidden;}
.tool button {height:100%; width:50px; float:right; clear:right; display:block;}
</style>
</head>
<body>
<div class="wrap">
	<div class="main">
		<div class="online">
			<div>当前在线：<span id="onlineCount">..</span></div>
		</div>
		<ul class="pop"></ul>
	</div>
	<div class="tool">
		<button type="button" id="sendBtn">发送</button>
		<textarea id="sendText"></textarea>
	</div>
</div>

<script src="/static/lib/jquery-2.2.4.js"></script>
<script>
var mainWrap = $(".wrap"), pop = $("ul.pop"), input = $("#sendText"), onlineCount = $("#onlineCount");
var ws;

function sendMsg() {
	var text = input.val();

	if ($.trim(text) == "") {
		addTip("输入内容不可以为空或者纯空格");
		return false;
	}

	var rnd = Math.random().toString().split(".")[1];

	addMessage('rnd' + rnd, '..', '我', text + ' <span class="message-send-status">...</span>', 1);
	input.val("").focus();

	ws.sendProxy("send", {msg:text, rnd:rnd});
}

function addMessage(id, time, nick, content, type) {
	var className = (!type || type == 0) ? "message-receive" : "message-send";
	var html = '<li class="message ' + className + '" id="' + id + '">'
		+ '<div class="message-head">'
		+ '<span class="message-nick">' + nick + '</span>[<span class="message-time">' + time + '</span>]'
		+ '</div><div class="message-content">' + content + '</div></li>';

	messageAppend(html);
}

function addTip(content) {
	messageAppend('<li class="tip"><span>' + content + '</span></li>');
}

function messageAppend(html) {
	pop.append(html);

	var listCount = parseInt(pop.data("listCount") || 0) + 1;

	if (listCount > 200) {
		pop.find("> li").slice(0, 20).remove();
		listCount = pop.find("> li").length;
	}

	pop.data("listCount", listCount);

	var st = pop.prop("scrollHeight") - pop.height();
	st > 0 && pop.animate({scrollTop:st}, "fast");
}

function adjustWindowSize() {
	mainWrap.height($(window).height());
}

function createConnection() {
	if (ws != null) {
		console.log("Connection is still on use, create failed!");
		return;
	}

	ws = new WebSocket("ws://" + location.hostname + ":8100");

	ws.onopen = function(event) {

		var self = this;

		this.pingTimer = null;

		this.setPing = function() {
			self.pingTimer = setTimeout(function() {
				var data = {type:"ping"};
				self.send(JSON.stringify(data));
			}, 30000);
		};

		this.clearPing = function() {
			clearTimeout(ws.pingTimer);
		};

		this.setPing();

		addTip("已建立连接");
	};

	ws.onmessage = function(event) {
		var data = JSON.parse(event.data);

		switch (data.type) {
			case "msg":
				addMessage('msg' + data.id, data.time, '某人', data.msg);
				break;

			case "send":
				var li = $("#rnd" + data.rnd);
				var statusContainer = li.find(".message-send-status");

				li.data("id", data.id);

				if (data.status == "done") {
					statusContainer.text("✔");
					li.find(".message-time").text(data.time);
				} else {
					statusContainer.text(data.status);
				}
				break;

			case "online_count":
				onlineCount.text(data.num);

				addTip('某用户' + (data.way == "in" ? "进入" : "离开") + '了房间');
				break;

			case "pong":
				addTip('Pong: ' + data.time);
				break;

			case "error":
				addTip(data.msg);
				break;
		}

		this.clearPing();
		this.setPing();

	};

	ws.onclose = function() {
		this.clearPing();
		ws = null;
		addTip("连接已断开，正在重连..");
		setTimeout(createConnection, 2000)
	};

	ws.sendProxy = function(type, data) {
		data.type = type;

		this.send(JSON.stringify(data));

		this.clearPing();
		this.setPing();
	};
}

createConnection();

$("#sendBtn").click(sendMsg);

//回车时发送
input.keydown(function(e) {
	if (e.keyCode == 13) {
		sendMsg();
		return false;
	}
});

adjustWindowSize();
$(window).resize(adjustWindowSize);
</script>
</body>
</html>