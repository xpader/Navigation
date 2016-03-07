<?php 
use \Workerman\Worker;
use \Workerman\Autoloader;
use \Navigation\Navi;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);

$worker = new Worker('http://0.0.0.0:8001');
$worker->count = 4;

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