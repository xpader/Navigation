<?php

namespace Applications\XChat;

use \PDO;

class Data {

	/**
	 * @var PDO
	 */
	private static $db;

	public static function init() {
		if (self::$db === null) {
			self::$db = new PDO('sqlite:' . __DIR__ . '/data/xchat.db');
		}
	}

	public static function getError() {
		$errorInfo = self::$db->errorInfo();
		return "[{$errorInfo[0]}]{$errorInfo[2]}";
	}

	public static function addMessage($uid, $nickname, $msg, $time, $ip) {
		//保存到数据库
		$sth = self::$db->prepare('INSERT INTO messages (`nickname`, `time`, `msg`, `uid`, `ip`) VALUES(?, ?, ?, ?, ?)');

		if (!$sth) {
			return false;
		}

		$result = $sth->execute([$nickname, $time, $msg, $uid, $ip]);

		return $result ? self::$db->lastInsertId() : false;
	}

	public static function getIpBlackList() {
		//读取 IP 黑名单
		$sth = self::$db->query('SELECT ip FROM ip_blacklist');

		if (!$sth) {
			return false;
		}

		$list = $sth->fetchAll(PDO::FETCH_NUM);

		return array_column($list, 0);
	}

	public static function addBlacklistIp($ip) {
		$sth = self::$db->prepare('INSERT INTO ip_blacklist (`ip`) VALUES(?)');

		if (!$sth) {
			return false;
		}

		return $sth->execute([$ip]);
	}

	public static function removeBlacklistIp($ip) {
		$sth = self::$db->prepare('DELETE FROM ip_blacklist WHERE ip=?');

		if (!$sth) {
			return false;
		}

		return $sth->execute([$ip]);
	}

	public static function addConnection($connection) {
		$sth = self::$db->prepare('INSERT INTO connections (`id`, `ip`, `uid`, `connect_time`) VALUES(?, ?, ?, ?)');

		if (!$sth) {
			return false;
		}

		return $sth->execute([$connection->id, $connection->getRemoteIp(), $connection->uid, $connection->lastActive]);
	}

	public static function removeConnection($id) {
		return self::$db->exec('DELETE FROM connections WHERE id='.self::$db->quote($id));
	}

	public static function clearConnections() {
		self::$db->exec('DELETE FROM connections');
	}

	public static function getConnectionsByIp($ip) {
		$sth = self::$db->prepare('SELECT * FROM connections WHERE ip=?');
		$sth->execute([$ip]);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

}
