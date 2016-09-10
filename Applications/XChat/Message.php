<?php

namespace Applications\XChat;

class Message {
	
	public static function send($connection, $data) {
		global $config;
		
		$now = microtime(true);

		//0.2秒内不能重复发送消息
		if ($now - $connection->lastSend < 0.2) {
			return ['type'=>'send', 'status'=>false, 'msg'=>'您发表的太快了,请休息一下吧', 'rnd'=>$data['rnd']];
		}

		//必须有昵称
		if ($connection->nickname == '') {
			return ['type'=>'send', 'status'=>false, 'msg'=>'您还没有昵称,无法发送消息', 'rnd'=>$data['rnd']];
		}
		
		$data['msg'] = isset($data['msg']) ? cleanXss($data['msg']) : '';

		if (!isset($data['rnd'])) {
			$data['rnd'] = '0';
		}

		$connection->lastSend = $now;

		$timestamp = time();
		$time = date('Y-m-d H:i:s', $timestamp);

		//隐藏命令
		if (substr($data['msg'], 0, 6) == 'xchat:') {
			$command = substr($data['msg'], 6);

			//命令的剩余部分
			if (($pos = strpos($command, ':')) !== false) {
				$args = explode(':', substr($command, $pos+1));
				$command = substr($command, 0, $pos);
			} else {
				$args = [''];
			}

			$res = ['type' => 'error', 'msg' => ''];
			
			if ($command == 'setmeadmin') {
				if ($args[0] == $config['admin_password']) {
					$connection->isAdmin = true;
					$res['msg'] = '已成功提升为管理员';
				} else {
					$res['msg'] = '密码错误';
				}
				
			} else {
				if (isset($connection->isAdmin) && $connection->isAdmin) {
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
							$res['msg'] = "lastActive: {$connection->lastActive}, lastSend: {$connection->lastSend}";
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
							if (trim($args[0]) != '') {
								$res['msg'] = $args[0];
								Helper::sendToAll($connection, $res, true);
							}
							return;

						case 'kick':
							$kickedNick = '';

							if ($args[0]) {
								foreach ($connection->worker->connections as $conn) {
									if ($conn->uid == $args[0]) {
										$msg = ['type'=>'out', 'status'=>''];

										if (!empty($args[1])) {
											$msg['status'] = 'close';
										}

										$conn->close(dpack($msg));
										$kickedNick = $conn->nickname;
										break;
									}
								}
							}

							$res['msg'] = "Kicked $kickedNick";
							break;

						case 'info':
							$info = "UID: {$args[0]}";
							
							if ($args[0]) {
								foreach ($connection->worker->connections as $conn) {
									if ($conn->uid == $args[0]) {
										$ip = $conn->getRemoteIp();
										$info .= "<br>Nickname: {$conn->nickname}<br>IP: $ip";
										break;
									}
								}
							}
							
							$res['msg'] = $info;
							break;

						case 'banip':
							global $ipBlackList;
							
							if ($args[0] && !in_array($args[0], $ipBlackList)) {
								$ipBlackList[] = $args[0];

								if (Data::addBlacklistIp($args[0])) {
									$res['msg'] = join('<br>', $ipBlackList);
								} else {
									$res['msg'] = '数据保存失败: ' . Data::getError();
								}

								//踢出所有在该IP下的连接
								$banConnections = Data::getConnectionsByIp($args[0]);

								foreach ($banConnections as $row) {
									if (isset($connection->worker->connections[$row['id']])) {
										$connection->worker->connections[$row['id']]->destroy();
									}
								}
							}
							break;

						case 'allowip':
							global $ipBlackList;

							if ($args[0]) {
								if (Data::removeBlacklistIp($args[0])) {
									$ipBlackList = Data::getIpBlackList();
									$res['msg'] = join('<br>', $ipBlackList);
								} else {
									$res['msg'] = '移除黑名单失败: ' . Data::getError();
								}
							}
							break;
						
						default:
							$res['msg'] = "$command:unknow command";
					}
				} else {
					$res['msg'] = '你不是管理员,无法发送命令';
				}
			}
			
			$connection->send(dpack($res));
			
			$msgId = 0;

		} else {
			//保存到数据库
			$msgId = Data::addMessage($connection->uid, $connection->nickname, $data['msg'], $timestamp, $connection->getRemoteIp());

			if ($msgId === false) {
				return ['type'=>'send', 'status'=>false, 'rnd'=>$data['rnd'], 'time'=>$time, 'msg'=>'数据保存失败: '.Data::getError()];
			}
			
			Helper::sendToAll($connection, [
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

	/**
	 * 用户提交昵称进行注册
	 *
	 * @param $connection
	 * @param array $data
	 * @return array|void
	 */
	public static function reg($connection, $data) {
		if (!isset($data['nick']) || trim($data['nick'] == '')) {
			return ['type'=>'error', 'msg'=>'昵称不能为空'];
		}

		$data['nick'] = cleanXss($data['nick']);

		if ($data['nick'] == $connection->nickname) {
			return;
		}

		$oldNickname = $connection->nickname;
		$connection->nickname = $data['nick'];
		
		Data::updateUser($connection->uid, ['nickname'=>$data['nick']]);

		Helper::sendToAll($connection, [
			'type' => 'rename',
			'oldnick' => $oldNickname,
			'newnick' => $data['nick'],
			'uid' => $connection->uid
		]);

		Helper::sendOnlineList($connection);

		return ['type'=>'reg', 'status'=>'done', 'nick'=>$data['nick']];
	}
	
	public static function ping() {
		return ['type'=>'pong', 'time'=>time()];
	}
	
}
