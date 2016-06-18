<?php 
use \Workerman\Worker;
use \Workerman\Autoloader;
use \Navigation\Navi;

// 非全局启动则自己加载 Workerman 的 Autoloader
if (!defined('GLOBAL_START')) {
	require_once __DIR__ . '/../../Workerman/Autoloader.php';
	Autoloader::setRootPath(__DIR__);
}

if (!defined('RUN_DIR')) {
	define('RUN_DIR', __DIR__ . '/../..');
}

$worker = new Worker('http://0.0.0.0:8001');
$worker->name = 'navi-app';
$worker->count = 1;

$worker->onWorkerStart = function($worker) {
	Navi::bootstrap(__DIR__.'/config.php', $worker);
};

$worker->onMessage = function($connection, $data) {
	Navi::request($connection, $data);
};

// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    Worker::runAll();
}