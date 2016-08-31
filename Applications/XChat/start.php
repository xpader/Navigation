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

$msgAutoId = 1;

$worker->onConnect = function($connection) {
	$connection->lastActive = microtime(true);
	$connection->nickname = '';
	$connection->uid = md5(rand(10000, 99999).'-'.$connection->id);

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
	]);
};

/**
 * @param \Workerman\Connection\TcpConnection $connection
 * @param mixed $data
 */
$worker->onMessage = function($connection, $data) use (&$msgAutoId) {
	$data = @json_decode($data, true);

	if (!is_array($data)) {
		$res = ['type'=>'error', 'msg'=>'数据格式错误'];
		$connection->send(json_encode($res));
		return;
	}

	switch ($data['type']) {
		case 'send':
			$now = microtime(true);

			//0.2秒内不能重复发送消息
			if ($now - $connection->lastActive < 0.2) {
				$res = ['type'=>'send', 'status'=>false, 'msg'=>'您发表的太快了,请休息一下吧', 'rnd'=>$data['rnd']];
				break;
			}

			if (!isset($data['msg'])) {
				$data['msg'] = '';
			} else {
				$data['msg'] = cleanXss($data['msg']);
			}

			if (!isset($data['rnd'])) {
				$data['rnd'] = '0';
			}

			$time = date('Y-m-d H:i:s');
			$msgId = $msgAutoId;
			++$msgAutoId;

            //隐藏命令
            if (substr($data['msg'], 0, 6) == 'xchat:') {
                $command = substr($data['msg'], 6);
	            $commandArg = '';
	            
	            //命令的剩余部分
	            if (($pos = strpos($command, ':')) !== false) {
		            $commandArg = substr($command, $pos+1);
		            $command = substr($command, 0, $pos);
	            }

                $res = ['type' => 'error', 'msg' => ''];

                switch ($command) {
                    case 'gc':
                        $gcNum = gc_collect_cycles();
                        $memory = byteFormat(memory_get_usage());
                        $memoryReal = byteFormat(memory_get_usage(true));
                        $res['msg'] = "gc: $gcNum, memory: $memory, real: $memoryReal";
                        break;

                    case 'mem':
                        $memory = byteFormat(memory_get_usage());
                        $memoryReal = byteFormat(memory_get_usage(true));
                        $res['msg'] = "memory: $memory, real: $memoryReal";
                        break;
	                
	                case 'la':
		                $res['msg'] = $connection->lastActive;
		                break;

	                case 'ko':
						$kickCount = 0;
		                foreach ($connection->worker->connections as $conn) {
			                if ($conn->id != $connection->id) {
				                $conn->destroy();
				                ++$kickCount;
			                }
		                }
		                $res['msg'] = "Kicked $kickCount connections";
		                break;
	                
	                case 'tip':
		                if (trim($commandArg) != '') {
			                $res['msg'] = $commandArg;
			                sendToAll($connection, $res, true);
		                }
						unset($res);
	                    break 2;
	                
                    default:
                        $res['msg'] = "$command:unknow command";
                }

                $connection->send(json_encode($res));

            } else {
                sendToAll($connection, [
                    'type' => 'msg',
                    'nick' => $connection->nickname,
                    'msg' => $data['msg'],
                    'id' => $msgId,
                    'uid' => $connection->uid,
                    'time' => $time
                ]);
            }

			$res = ['type'=>'send', 'status'=>true, 'rnd'=>$data['rnd'], 'id'=>$msgId, 'time'=>$time];
			break;
		
		case 'ping':
			$res = ['type'=>'pong', 'time'=>time()];
			break;
		
		case 'reg':
			if (!isset($data['nick']) || trim($data['nick'] == '')) {
				$res = ['type'=>'error', 'msg'=>'昵称不能为空'];
				break;
			}

			$data['nick'] = cleanXss($data['nick']);

			$oldNickname = $connection->nickname;
			$connection->nickname = $data['nick'];

			sendToAll($connection, [
				'type' => 'rename',
				'oldnick' => $oldNickname,
				'newnick' => $data['nick'],
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
		$connection->send(json_encode($res, JSON_UNESCAPED_UNICODE));
	}

	$connection->lastActive = microtime(true);
};

function sendToAll($connection, $res, $includeSelf=false) {
	$res = json_encode($res, JSON_UNESCAPED_UNICODE);

	$expires = microtime(true) - 60;
	
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
	}
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