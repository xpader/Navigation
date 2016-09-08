<?php
use \Workerman\Worker;
use \Workerman\Autoloader;
use \Applications\XChat\Data;

// 非全局启动则自己加载 Workerman 的 Autoloader
if (!defined('GLOBAL_START')) {
	require_once __DIR__ . '/../../Workerman/Autoloader.php';
	Autoloader::setRootPath(__DIR__);
}

$worker = new Worker('websocket://0.0.0.0:8100');
$worker->name = 'xchat-server';
$worker->count = 1;

/**
 * IP 黑名单列表
 */
$ipBlackList = [];

/**
 * @var $worker->db PDO
 */

$worker->onWorkerStart = function($worker) {
    global $ipBlackList;

	Data::init();
	Data::clearConnections();

	$ipBlackList = Data::getIpBlackList();

	if ($ipBlackList === false) {
		echo '读取黑名单失败: '.Data::getError();
		$ipBlackList = [];
	}
};

$worker->onConnect = function($connection) {
	global $ipBlackList;

	if (in_array($connection->getRemoteIp(), $ipBlackList)) {
		$connection->destroy();
		return;
	}
	
	$connection->uid = md5(rand(10000, 99999).'-'.$connection->id);
	$connection->nickname = '';
	$connection->lastActive = time(); //最后活跃时间
	$connection->lastSend = microtime(true); //最后发送消息时间

	Data::addConnection($connection);

	$connection->send(dpack(['type'=>'baseinfo', 'uid'=>$connection->uid]));

	$onlineCount = count($connection->worker->connections);
	
	sendToAll($connection, [
		'type' => 'online_count',
		'nick' => $connection->nickname,
		'num' => $onlineCount,
		'way' => 'in',
		'uid' => $connection->uid
	], true);
};

$worker->onClose = function($connection) {
	if (!isset($connection->uid)) {
		return;
	}

	Data::removeConnection($connection->id);

	$onlineCount = count($connection->worker->connections);
	
	sendToAll($connection, [
		'type' => 'online_count',
		'nick' => $connection->nickname,
		'num' => $onlineCount,
		'way' => 'leave',
		'uid' => $connection->uid
	]);
};

require __DIR__.'/config.php';

/**
 * @param \Workerman\Connection\TcpConnection $connection
 * @param mixed $data
 */
$worker->onMessage = function($connection, $data) {
	$data = @json_decode($data, true);

	if (!is_array($data) || !isset($data['type'])) {
		$res = ['type'=>'error', 'msg'=>'数据格式错误'];
		$connection->send(dpack($res));
		return;
	}

	//调用消息
	$call = '\Applications\XChat\Message::'.$data['type'];
	
	if (is_callable($call)) {
		$res = call_user_func_array($call, [$connection, $data]);
	} else {
		$res = ['type'=>'error', 'msg'=>'数据格式错误'];
	}

	if (isset($res) && is_array($res)) {
		$connection->send(dpack($res, JSON_UNESCAPED_UNICODE));
	}

	$connection->lastActive = time();
};

/**
 * 将数据发送给所有连接
 *
 * @param \Workerman\Connection\TcpConnection $connection 当前连接
 * @param array $res
 * @param bool $includeSelf 是否发送给自己，默认不发
 */
function sendToAll($connection, $res, $includeSelf=false) {
	$res = dpack($res);

	$expires = time() - 60;
	
	foreach ($connection->worker->connections as $conn) {
		if (!$includeSelf && $conn->id == $connection->id) {
			continue;
		}
		
		//移除不活跃的链接
		if ($conn->lastActive < $expires) {
			$conn->close();
			continue;
		}
		
		$conn->send($res);
		$conn->lastActive = time();
	}
}

/**
 * 代理数据格式封装
 *
 * @param array $data
 * @return string
 */
function dpack($data) {
	return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * 清理 HTML 中的 XSS 潜在威胁
 *
 * 千辛万苦写出来，捣鼓正则累死人
 *
 * @param string|array $string
 * @param bool $strict 严格模式下，iframe 等元素也会被过滤
 * @return mixed
 */
function cleanXss($string, $strict=true) {
	if (is_array($string)) {
		return array_map('cleanXss', $string);
	}

	//移除不可见的字符
	$string = preg_replace('/%0[0-8bcef]/', '', $string);
	$string = preg_replace('/%1[0-9a-f]/', '', $string);
	$string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

	$string = preg_replace('/<meta.+?>/is', '', $string); //过滤 meta 标签
	$string = preg_replace('/<link.+?>/is', '', $string); //过滤 link 标签
	$string = preg_replace('/<script.+?<\/script>/is', '', $string); //过滤 script 标签

	if ($strict) {
		$string = preg_replace('/<style.+?<\/style>/is', '', $string); //过滤 style 标签
		$string = preg_replace('/<iframe.+?<\/iframe>/is', '', $string); //过滤 iframe 标签 1
		$string = preg_replace('/<iframe.+?>/is', '', $string); //过滤 iframe 标签 2
	}

	$string = preg_replace_callback('/(\<\w+\s)(.+?)(?=( \/)?\>)/is', function($m) {
		//去除标签上的 on.. 开头的 JS 事件，以下一个 xxx= 属性或者尾部为终点
		$m[2] = preg_replace('/\son[a-z]+\s*\=.+?(\s\w+\s*\=|$)/is', '\1', $m[2]);

		//去除 A 标签中 href 属性为 javascript: 开头的内容
		if (strtolower($m[1]) == '<a ') {
			$m[2] = preg_replace('/href\s*=["\'\s]*javascript\s*:.+?(\s\w+\s*\=|$)/is', 'href="#"\1', $m[2]);
		}

		return $m[1].$m[2];
	}, $string);

	$string = preg_replace('/(<\w+)\s+/is', '\1 ', $string); //过滤标签头部多余的空格
	$string = preg_replace('/(<\w+.*?)\s*?( \/>|>)/is', '\1\2', $string); //过滤标签尾部多余的空格

	return $string;
}

function byteFormat($filesize) {
	$units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	$i = floor(log($filesize, 1024));
	return $filesize ? number_format($filesize/pow(1024, $i), 2, '.', '') . $units[(int)$i] : '0 Bytes';
}

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
	Worker::runAll();
}