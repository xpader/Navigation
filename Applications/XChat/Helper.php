<?php

namespace Applications\XChat;

class Helper {
	
	public static function userStateChange($connection, $state) {
		$onlineCount = count($connection->worker->connections);

		Helper::sendToAll($connection, [
			'type' => 'user_state_change',
			'nick' => $connection->nickname,
			'num' => $onlineCount,
			'state' => $state ? 'online' : 'offline',
			'uid' => $connection->uid
		]);
	}
	
	public static function error($connection, $message) {
		Helper::send($connection, ['type'=>'error', 'msg'=>$message]);
	}

	/**
	 * 向指定的链接发送在线用户列表
	 * 
	 * @param $connection
	 */
	public static function sendOnlineList($connection) {
		//在线用户列表
		$list = [];

		foreach ($connection->worker->connections as $conn) {
			if ($conn->uid) {
				$list[$conn->uid] = $conn->nickname;
			}
		}

		$res = ['type'=>'online_list', 'num'=>count($connection->worker->connections), 'onlineList'=>$list];
		
		$connection->send(dpack($res));
	}
	
	public static function send($connection, $data) {
		$connection->send(dpack($data));
	}

	/**
	 * 将数据发送给所有连接
	 *
	 * @param \Workerman\Connection\TcpConnection $connection 当前连接
	 * @param array $res
	 * @param bool $includeSelf 是否发送给自己，默认不发
	 */
	public static function sendToAll($connection, $res, $includeSelf=false) {
		$res = dpack($res);

		$now = time();
		$expires = $now - 60;

		foreach ($connection->worker->connections as $conn) {
			if (!$includeSelf && $conn->id == $connection->id) {
				continue;
			}

			if (!isset($connection->uid)) {
				continue;
			}

			//移除不活跃的链接
			if ($conn->lastActive < $expires) {
				$conn->close();
				continue;
			}

			$conn->send($res);
			$conn->lastActive = $now;
		}
	}
	
}
