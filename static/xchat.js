var mainWrap = $(".wrap"), pop = $("ul.pop"), input = $("#sendText"), onlineCount = $("#onlineCount"), onlineList = $("#onlineList"),
	lastActive = $("#lastActive"), bottomArea = $("#bottomArea"), sendBtn = $("#sendBtn");
var ws, lastActiveTime = now(), nickname = "";

function now() {
	return parseInt((new Date()).getTime() / 1000);
}

function sendMsg() {
	if (ws == null) {
		addTip("与服务器连接失败,无法发送消息");
		return false;
	}

	var text = input.val();

	if ($.trim(text) == "") {
		addTip("输入内容不可以为空或者纯空格");
		return false;
	}

	var rnd = Math.random().toString().split(".")[1];

	addMessage('rnd' + rnd, '..', nickname, text + ' <span class="message-send-status">...</span>', 1);

	ws.sendProxy("send", {msg:text, rnd:rnd});

	setTimeout(function() {
		input.val("").focus();
	}, 0);
}

function addMessage(id, time, nick, content, type) {
	var className = (!type || type == 0) ? "message-receive" : "message-send";
	var html = '<li class="message ' + className + '" id="' + id + '">'
		+ '<div class="message-head">'
		+ '<span class="message-nick">' + nick + '</span>[<span class="message-time">' + time + '</span>]'
		+ '</div><div class="message-content">' + content + '</div></li>';

	messageAppend(html, type == 1);
}

function addTip(content) {
	messageAppend('<li class="tip"><span>' + content + '</span></li>');
}

function messageAppend(html, forceScroll) {
	//是否需要滚动
	var popHeight = pop.innerHeight();

	if (!forceScroll) {
		pop.stop(false, true);
		forceScroll = (pop.prop("scrollHeight") - (pop.scrollTop() + popHeight) < 50);
	}

	pop.append(html);

	var listCount = parseInt(pop.data("listCount") || 0) + 1;

	if (listCount > 200) {
		pop.find("> li").slice(0, 20).remove();
		listCount = pop.find("> li").length;
	}

	pop.data("listCount", listCount);

	if (forceScroll) {
		pop.animate({scrollTop:pop.prop("scrollHeight") - popHeight}, 100);
	}
}

function checkNickname() {
	if (nickname == "") {
		nickname = prompt("请先设置一个昵称:");

		if ($.trim(nickname) == "") {
			addTip("必须设置一个有效的昵称才能发送消息");
			return false;
		}
	}
}

function createConnection() {
	if (ws != null) {
		console.log("Connection is still on use, create failed!");
		return;
	}

	try {
		ws = new WebSocket("ws://" + location.hostname + ":8100");
	} catch (e) {
		addTip("建立连接失败");
		console.log(e);
		return;
	}

	ws.onopen = function(event) {
		var self = this;

		addTip("已建立连接");

		if (nickname != "") {
			this.sendProxy("reg", {nick: nickname});
		}

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
				addMessage('msg' + data.id, data.time, (data.nick ? data.nick : '路人甲'), data.msg);
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

				var nick = (data.nick ? data.nick : '路人甲');

				if (data.way == "in") {
					onlineList.append('<li data-uid="' + data.uid + '">' + nick + '</li>');
				} else {
					onlineList.find(">li[data-uid='" + data.uid + "']").remove();
				}

				addTip(nick + " " + (data.way == "in" ? "进入" : "离开") + '了房间');
				break;

			case "rename":
				addTip((data.oldnick ? data.oldnick : '路人甲') + " 改名为 " + data.newnick);
				onlineList.find(">li[data-uid='" + data.uid + "']").html(data.newnick);
				break;

			case "pong":
				lastActiveTime = now();
				break;

			case "reg":
				if (data.status == "done") {
					nickname = data.nick;
					bottomArea.find(".tool-name-reg").hide();

					setTimeout(function() {
						input.focus();
					}, 250);

					var html = '';

					for (var uid in data.onlineList) {
						if (data.onlineList.hasOwnProperty(uid)) {
							html += '<li data-uid="' + uid + '">' + data.onlineList[uid] + '</li>';
						}
					}

					onlineList.html(html);
				} else {
					addTip(data.msg);
				}
				break;

			case "error":
				addTip(data.msg);
				break;
		}

		this.clearPing();
		this.setPing();

	};

	ws.onclose = function() {
		if (this.readyState != 3) {
			this.clearPing();
		}

		ws = null;
		addTip("连接已断开，正在重连..");
		setTimeout(createConnection, 3000)
	};

	ws.onerror = function(e) {
		console.log(e);
	};

	ws.sendProxy = function(type, data) {
		data.type = type;

		this.send(JSON.stringify(data));

		this.clearPing();
		this.setPing();
	};
}

function adjustWindowSize() {
	mainWrap.height($(window).height());
	input.width(bottomArea.width() - sendBtn.outerWidth() - 10);
}

$(function(){
	adjustWindowSize();

	createConnection();

	//注册昵称
	if (nickname == "") {
		var nameReg = bottomArea.find(".tool-name-reg");

		nameReg.find("form").submit(function() {
			if ($.trim(this.mynick.value) == "") {
				addTip("请输入有效的昵称");
				return false;
			}

			ws.sendProxy("reg", {nick: this.mynick.value});
			nickname = this.mynick.value;

			return false;
		});
	}

	sendBtn.click(sendMsg);

	//回车时发送
	input.keydown(function(e) {
		if (e.keyCode == 13) {
			return false;
		}
	}).keyup(function(e) {
		if (e.keyCode == 13) {
			sendMsg();
			return false;
		}
	});

	var pongViewTimer = setInterval(function() {
		var ago = now() - lastActiveTime;
		lastActive.text(ago);
	}, 3500);

	$(window).resize(adjustWindowSize);
});