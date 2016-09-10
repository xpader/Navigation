var mainWrap = $(".wrap"), pop = $("ul.pop"), input = $("#sendText"), statusBar = mainWrap.find("> .statusbar"),
	onlineCount = $("#onlineCount"), onlineList = $("#onlineList"),
	lastActive = $("#lastActive"), bottomArea = $("#bottomArea"), sendBtn = $("#sendBtn"),
	msgSound = document.getElementById("msgSound"), notiSound = notiTitle = notiBrowser = false;

var ws, lastActiveTime = now(), nickname = $.cookie("nickname"), uid,
	hidden, visibilityChange, visibilityState = true,
	origTitle = document.title, blink;

function now() {
	return parseInt((new Date()).getTime() / 1000);
}

function sendMsg() {
	if (ws == null) {
		showStatus("与服务器连接失败,无法发送消息");
		return false;
	}

	var text = input.val();

	if ($.trim(text) == "") {
		showStatus("输入内容不可以为空或者纯空格", true);
		input.focus();
		return false;
	}

	var rnd = Math.random().toString().split(".")[1];

	addMessage('rnd' + rnd, '...', nickname, text, 1);

	ws.sendProxy("send", {msg:text, rnd:rnd});

	setTimeout(function() {
		input.val("").focus();
	}, 0);
}

function buildMessageHtml(id, time, nick, content, type) {
	var className = (!type || type == 0) ? "message-receive" : "message-send";
	return '<li class="message ' + className + '" id="' + id + '">'
		+ '<div class="message-head">'
		+ '<span class="message-nick">' + nick + '</span><span class="message-time">' + time + '</span>'
		+ '</div><div class="message-content">' + content + '</div></li>';
}

function addMessage(id, time, nick, content, type) {
	var html = buildMessageHtml(id, time, nick, content, type);
	messageAppend(html, type == 1);
}

function addTip(content) {
	messageAppend('<li class="tip"><span>' + content + '</span></li>');
}

function messageAppend(html, forceScroll, prepend) {
	//是否需要滚动
	var popHeight = pop.innerHeight();

	if (!forceScroll) {
		pop.stop(false, true);
		forceScroll = (pop.prop("scrollHeight") - (pop.scrollTop() + popHeight) < 50);
	}

	if (prepend) {
		pop.prepend(html);
	} else {
		pop.append(html);
	}

	var listCount = parseInt(pop.data("listCount") || 0) + 1;

	if (listCount > 200) {
		pop.find("> li").slice(0, 20).remove();
		listCount = pop.find("> li").length;
	}

	pop.data("listCount", listCount);

	if (forceScroll) {
		pop.animate({scrollTop:pop.prop("scrollHeight") - popHeight}, 250);
	}
}

function checkNickname() {
	if (nickname == "") {
		nickname = prompt("请先设置一个昵称:");

		if ($.trim(nickname) == "") {
			showStatus("必须设置一个有效的昵称才能发送消息", true);
			return false;
		}
	}
}

function getNick(nick) {
	return nick ? nick : '路人甲';
}

function showStatus(text, afterHide) {
	statusBar.text(text).show();

	if (afterHide) {
		//默认两秒后隐藏
		if (afterHide === true) {
			afterHide = 2000;
		}

		setTimeout(function() {
			statusBar.fadeOut("fast");
		}, afterHide);
	}
}

function hideStatus() {
	statusBar.fadeOut("fast");
}

function createConnection() {
	if (ws != null) {
		console.log("Connection is still on use, create failed!");
		return;
	}

	ws = new WebSocket("ws://" + location.hostname + ":8100/?hash=" + $("#userHash").val());
	ws.autoReconnect = true;

	var pingTimer = null;

	ws.setPing = function() {
		pingTimer = setTimeout(function() {
			var data = {type:"ping"};
			ws.send(JSON.stringify(data));
		}, 30000);
	};

	ws.clearPing = function() {
		clearTimeout(pingTimer);
	};

	ws.onopen = function(event) {
		showStatus("已建立连接", true);

		//注册名称
		if (nickname) {
			this.sendProxy("reg", {nick: nickname});
		}

		ws.setPing();
	};

	ws.onmessage = function(event) {
		var data = JSON.parse(event.data);
		MessageEvents[data.type].call(ws, data);
		ws.clearPing();
		ws.setPing();
	};

	ws.onclose = function() {
		ws.clearPing();

		if (ws.autoReconnect) {
			showStatus("连接已断开，正在重连..");
			setTimeout(createConnection, 3000)
		}

		ws = null;
	};

	ws.onerror = function(e) {
		console.log(e);
	};

	ws.sendProxy = function(type, data) {
		data.type = type;

		ws.send(JSON.stringify(data));

		ws.clearPing();
		ws.setPing();
	};
}

function showOnlineList(olist) {
	var html = '';
	for (var uid in olist) {
		if (olist.hasOwnProperty(uid)) {
			html += '<li data-uid="' + uid + '">' + getNick(olist[uid]) + '</li>';
		}
	}
	onlineList.html(html);
}

var MessageEvents = {
	"msg": function(data) {
		var nick = getNick(data.nick);
		var type = data.uid == uid ? 1 : 0;

		addMessage('msg' + data.id, data.time, nick, data.msg, type);

		if (type == 1) return;

		if (notiSound && msgSound.error == null) {
			msgSound.play();
		}

		if (!visibilityState) {
			if (blink == null && notiTitle) {
				var blinkState = 0;
				blink = setInterval(function () {
					if (blinkState == 0) {
						document.title = "新消息 - " + origTitle;
						blinkState = 1;
					} else {
						document.title = origTitle;
						blinkState = 0;
					}
				}, 1000);
			}

			if (notiBrowser) {
				Push.clear();
				Push.create("XChat", {
					body: nick + ": " + data.msg.substr(0, 20) + "..",
					icon: $("link[rel='shortcut icon']").attr("href"),
					timeout: 4000,
					onClick: function () {
						window.focus();
						this.close();
					}
				});
			}
		}
	},
	"send": function(data) {
		var li = $("#rnd" + data.rnd);

		if (data.status) {
			li.data("id", data.id);
			li.find(".message-time").text(data.time);
		} else {
			li.find(".message-time").text('..发送失败').css("color", "#F00");
			addTip(data.msg);
		}
	},
	"user_state_change": function(data) {
		onlineCount.text(data.num);
		
		var nick = getNick(data.nick);

		if (data.state == "online") {
			onlineList.append('<li data-uid="' + data.uid + '">' + nick + '</li>');
		} else {
			onlineList.find(">li[data-uid='" + data.uid + "']").remove();
		}

		if (data.uid != uid) {
			addTip(nick + " " + (data.state == "online" ? "进入" : "离开") + '了房间');
		}
	},
	"online_list": function(data) {
		onlineCount.text(data.num);
		showOnlineList(data.onlineList);
	},
	"rename": function(data) {
		addTip(getNick(data.oldnick) + " 改名为 " + data.newnick);
		onlineList.find(">li[data-uid='" + data.uid + "']").html(data.newnick);
	},
	"pong": function() {
		lastActiveTime = now();
	},
	"baseinfo": function(data) {
		uid = data.uid;

		if (data.nickname) {
			nickname = data.nickname;
			bottomArea.find(".tool-name-reg").hide();
			
			setTimeout(function () {
				input.focus();
			}, 250);
		}

		//加载历史记录
		$.getJSON("/xchat/getHistory", function(history) {
			var html = '', type;

			for (var i=0,row; row=history[i]; i++) {
				type = row.uid != uid ? 0 : 1;
				html += buildMessageHtml('msg' + row.id, row.time, row.nickname, row.msg, type);
			}

			if (html != '') {
				html += '<li class="split"><span>以上是历史消息</span></li>';
				messageAppend(html, true, true);
			}
		});
	},
	"reg": function(data) {
		if (data.status == "done") {
			nickname = data.nick;
			bottomArea.find(".tool-name-reg").hide();

			setTimeout(function () {
				input.focus();
			}, 250);

			showOnlineList(data.onlineList);
		} else {
			showStatus(data.msg, true);
		}
	},
	"out": function(data) {
		ws.autoReconnect = false;
		ws.close();

		switch (data.status) {
			case "close":
				location.href = "https://www.baidu.com/";
				break;
			case "replaced":
				showStatus("您已在别处登录,当前连接已断开");
				break;
			default:
				showStatus("您已被踢出");
		}
	},
	"error": function(data) {
		addTip(data.msg);
	}
};

function adjustWindowSize() {
	var winWidth = $(window).width();
	var body = $("body");

	if (winWidth > 600) {
		body.removeClass("mobile");
		mainWrap.css({"height": ($(window).height() - 36) + "px", "border-radius":"5px", "margin":"18px auto"});
	} else {
		body.addClass("mobile");
		mainWrap.css({"height":"100%", "border-radius":"0px", "margin":"0 auto"});
	}
	//input.width(bottomArea.width() - 50 - 10);
}

adjustWindowSize();
createConnection();

//注册昵称
if (!nickname) {
	var nameReg = bottomArea.find(".tool-name-reg");
	nameReg.find(">p").remove();
	nameReg.find("form").submit(function() {
		if ($.trim(this.mynick.value) == "") {
			showStatus("请输入有效的昵称", true);
			return false;
		}

		ws.sendProxy("reg", {nick: this.mynick.value});
		nickname = this.mynick.value;
		$.cookie("nickname", nickname, {path:location.pathname, expires:7});

		return false;
	}).show();
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

$("#notiSetting").find(":checkbox").each(function() {
	var cookieName = $(this).attr("name"), openState;

	var cv = $.cookie(cookieName);
	if (cv !== undefined) {
		openState = cv == 1;
		$(this).prop("checked", openState);
	} else {
		openState = $(this).prop("checked");
	}

	window[cookieName] = openState;

	$(this).click(function() {
		var openState = $(this).prop("checked");
		$.cookie(cookieName, openState ? 1 : 0, {path:location.pathname, expires:7});
		window[cookieName] = openState;
	});
});

var pongViewTimer = setInterval(function() {
	var ago = now() - lastActiveTime;
	lastActive.text(ago);
}, 3500);

$(window).resize(adjustWindowSize);

//Page visibility state
if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
	hidden = "hidden";
	visibilityChange = "visibilitychange";
} else if (typeof document.mozHidden !== "undefined") {
	hidden = "mozHidden";
	visibilityChange = "mozvisibilitychange";
} else if (typeof document.msHidden !== "undefined") {
	hidden = "msHidden";
	visibilityChange = "msvisibilitychange";
} else if (typeof document.webkitHidden !== "undefined") {
	hidden = "webkitHidden";
	visibilityChange = "webkitvisibilitychange";
}

if (document.addEventListener && typeof document[hidden] != "undefined") {
	document.addEventListener(visibilityChange, function() {
		visibilityState = !document[hidden];

		if (blink != null) {
			clearInterval(blink);
			blink = null;
			document.title = origTitle;
		}
	}, false);
}