<?php

namespace Applications\XChat;

class Message {
	
	public function send($connection, $data) {
		$now = microtime(true);

		//0.2秒内不能重复发送消息
		if ($now - $connection->lastActive < 0.2) {
			return ['type'=>'send', 'status'=>false, 'msg'=>'您发表的太快了,请休息一下吧', 'rnd'=>$data['rnd']];
		}

		if (!isset($data['msg'])) {
			$data['msg'] = '';
		} else {
			$data['msg'] = cleanXss($data['msg']);
		}

		if (!isset($data['rnd'])) {
			$data['rnd'] = '0';
		}

		$timestamp = time();
		$time = date('Y-m-d H:i:s', $timestamp);

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
					return;

				default:
					$res['msg'] = "$command:unknow command";
			}

			$connection->send(json_encode($res));

		} else {
			/**
			 * 保存到数据库
			 *
			 * @var $db \PDO
			 */
			$db = $connection->worker->db;
			$sth = $db->prepare("INSERT INTO messages (`nickname`, `time`, `msg`, `uid`, `ip`) VALUES(?, ?, ?, ?, ?)");

			if ($sth) {
				$sth->execute([$connection->nickname, $timestamp, $data['msg'], $connection->uid, $connection->getRemoteIp()]);
				$msgId = $db->lastInsertId();
			} else {
				$errorInfo = $db->errorInfo();
				return ['type'=>'send', 'status'=>false, 'rnd'=>$data['rnd'], 'time'=>$time, 'msg'=>"数据保存失败:[{$errorInfo[0]}]{$errorInfo[2]}"];
			}
			
			sendToAll($connection, [
				'type' => 'msg',
				'nick' => $connection->nickname,
				'msg' => $data['msg'],
				'id' => $msgId,
				'uid' => $connection->uid,
				'time' => $time
			]);
		}

		return ['type'=>'send', 'status'=>true, 'rnd'=>$data['rnd'], 'id'=>$msgId, 'time'=>$time];
	}
	
	public function reg($connection, $data) {
		if (!isset($data['nick']) || trim($data['nick'] == '')) {
			return ['type'=>'error', 'msg'=>'昵称不能为空'];
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

		return ['type'=>'reg', 'status'=>'done', 'nick'=>$data['nick'], 'onlineList'=>$list];
	}
	
	public function ping() {
		return ['type'=>'pong', 'time'=>time()];
	}
	
}
