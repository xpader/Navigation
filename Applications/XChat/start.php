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
	//$connection->username = '123';

	//$connection->onWebsocketConnect = function($connection) {
	//	$connection->send(json_encode(['type'=>'init', 'id'=>$connection->id]));\
	//};
	//
	//$connection->onWebsocketClose = function() {
	//};

	$onlineCount = count($connection->worker->connections);

	foreach ($connection->worker->connections as $conn) {
		$conn->send(json_encode(['type'=>'online_count', 'num'=>$onlineCount, 'way'=>'in']));
	}
};

$worker->onClose = function($connection) {
	$onlineCount = count($connection->worker->connections);

	foreach ($connection->worker->connections as $conn) {
		$conn->send(json_encode(['type'=>'online_count', 'num'=>$onlineCount, 'way'=>'leave']));
	}
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

			$msgId = $connection->worker->msgAutoId;
			++$connection->worker->msgAutoId;

			$time = date('Y-m-d H:i:s');

			$res = json_encode(['type'=>'msg', 'msg'=>$data['msg'], 'id'=>$msgId, 'time'=>$time]);

			foreach ($connection->worker->connections as $conn) {
				if ($conn->id != $connection->id) {
					$conn->send($res);
				}
			}

			$res = ['type'=>'send', 'status'=>'done', 'rnd'=>$data['rnd'], 'id'=>$msgId, 'time'=>$time];
			break;
		case 'ping':
			$res = ['type'=>'pong', 'time'=>time()];
			break;
		default:
			$res = ['type'=>'error', 'msg'=>'数据格式错误'];
	}

	if (isset($res)) {
		$connection->send(json_encode($res));
	}

	$connection->lastActive = time();
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
	Worker::runAll();
}