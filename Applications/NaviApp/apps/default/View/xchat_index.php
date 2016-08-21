<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>XChat</title>
<style type="text/css">
html, body {margin:0;}
body {background-color:#2E476B; color:#D5D5D5;}
.main {height:500px; width:500px; margin:0 auto;}
.main > * {height:100%;}
.online {display:none;}
/*
.pop {width:auto; overflow:hidden; zoom:1; border-right:1px solid #CCC;}
.online {width:200px; float:right; clear:right;}
*/
.send-status {color:#4AEF21; margin-left:10px; font-size:10px;}
.send-status:before {content:"[";}
.send-status:after {content:"]";}
.tool {position:absolute; z-index:1; height:50px; left:50%; margin-left:-250px; bottom:10px; width:500px;}
</style>
</head>
<body>
<div class="main">
	<div class="online"></div>
	<ul class="pop"></ul>
</div>
<div class="tool">
	<textarea id="sendText"></textarea>
	<button type="button" id="sendBtn">发送</button>
</div>

<script src="//cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
<script>
var pop = $("ul.pop");
var input = $("#sendText");

function sendMsg(ws, msg, rnd) {
	ws.sendProxy("send", {msg:msg, rnd:rnd});
}

$("#sendBtn").click(function() {
	var text = input.val();
	var rnd = Math.random().toString().split(".")[1];;

	pop.append('<li id="rnd' + rnd + '">Send: ' + text + ' <span class="send-status">发送中..</span></li>');

	sendMsg(ws, text, rnd);

	input.val("").focus();
});

var ws = new WebSocket("ws://127.0.0.1:8100");

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
};

ws.onmessage = function(event) {
	var data = JSON.parse(event.data);

	switch (data.type) {
		case "msg":
			pop.append('<li id="msg' + data.id + '">Message: ' + data.msg + '</li>');
			break;

		case "send":
			var li = $("#rnd" + data.rnd);
			var statusContainer = li.find(".send-status");

			li.data("id", data.id);

			if (data.status == "done") {
				statusContainer.text("发送成功");
			} else {
				statusContainer.text(data.status);
			}
			break;

		case "pong":
			pop.append("<li>Pong: " + data.time + "</li>");
			break;

		case "error":
			pop.append("<li>" + data.msg + "</li>");
			break;
	}

	this.clearPing();
	this.setPing();

};

ws.sendProxy = function(type, data) {
	data.type = type;

	this.send(JSON.stringify(data));

	this.clearPing();
	this.setPing();
};
</script>
</body>
</html>