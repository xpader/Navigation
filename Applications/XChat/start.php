<?php
use \Workerman\Worker;
use \Workerman\Autoloader;

// 非全局启动则自己加载 Workerman 的 Autoloader
if (!defined('GLOBAL_START')) {
	require_once __DIR__ . '/../../Workerman/Autoloader.php';
	Autoloader::setRootPath(__DIR__);
}

$worker = new Worker('websocket://0.0.0.0:8100');
$worker->name = 'xchat-server';
$worker->count = 1;

$worker->onWorkerStart = function($worker) {
	$worker->msgAutoId = 1;
};

$worker->onConnect = function($connection) {
	$connection->lastActive = time();
	$connection->nickname = '';
	$connection->uid = md5('xchat'.$connection->id);

	$onlineCount = count($connection->worker->connections);
	
	sendToAll($connection, [
		'type'=>'online_count',
		'nick'=>$connection->nickname,
		'num'=>$onlineCount,
		'way'=>'in',
		'uid'=>$connection->uid
	], true);
};

$worker->onClose = function($connection) {
	$onlineCount = count($connection->worker->connections);
	
	sendToAll($connection, [
		'type'=>'online_count',
		'nick'=>$connection->nickname,
		'num'=>$onlineCount,
		'way'=>'leave',
		'uid'=>$connection->uid
	], true);
};

/**
 * @param \Workerman\Connection\TcpConnection $connection
 * @param mixed $data
 */
$worker->onMessage = function($connection, $data) {
	$data = @json_decode($data, true);

	if (!is_array($data)) {
		$res = ['type'=>'error', 'msg'=>'数据格式错误'];
		$connection->send(json_encode($res));
		return;
	}

	switch ($data['type']) {
		case 'send':
			if (!isset($data['msg'])) {
				$data['msg'] = '';
			}

			if (!isset($data['rnd'])) {
				$data['rnd'] = '0';
			}

			$time = date('Y-m-d H:i:s');
			$msgId = $connection->worker->msgAutoId;
			++$connection->worker->msgAutoId;

			sendToAll($connection, [
				'type'=>'msg',
				'nick'=>$connection->nickname,
				'msg'=>$data['msg'],
				'id'=>$msgId,
				'uid'=>$connection->uid,
				'time'=>$time
			]);

			$res = ['type'=>'send', 'status'=>'done', 'rnd'=>$data['rnd'], 'id'=>$msgId, 'time'=>$time];
			break;
		
		case 'ping':
			$res = ['type'=>'pong', 'time'=>time()];
			break;
		
		case 'reg':
			if (!isset($data['nick']) || trim($data['nick'] == '')) {
				$res = ['type'=>'error', 'msg'=>'昵称不能为空'];
				break;
			}

			$oldNickname = $connection->nickname;
			$connection->nickname = $data['nick'];

			sendToAll($connection, [
				'type'=>'rename',
				'oldnick'=>$oldNickname,
				'newnick'=>$data['nick'],
				'uid' => $connection->uid
			]);

			//在线用户列表
			$list = [];

			foreach ($connection->worker->connections as $conn) {
				$list[$conn->uid] = $conn->nickname;
			}
			
			$res = ['type'=>'reg', 'status'=>'done', 'nick'=>$data['nick'], 'onlineList'=>$list];
			break;
		
		default:
			$res = ['type'=>'error', 'msg'=>'数据格式错误'];
	}

	if (isset($res)) {
		$connection->send(json_encode($res));
	}

	$connection->lastActive = time();
};

function sendToAll($connection, $res, $includeSelf=false) {
	$res = json_encode($res);
	
	foreach ($connection->worker->connections as $conn) {
		if (!$includeSelf && $conn->id == $connection->id) {
			continue;
		}
		
		$conn->send($res);
	}
}

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
	Worker::runAll();
}