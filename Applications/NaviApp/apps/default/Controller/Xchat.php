<?php

namespace Wide\Controller;

use Workerman\Protocols\Http;

class XChat extends \Controller {
	
	public function __construct() {
		parent::__construct();

		$dbFile = RUN_DIR.'/Applications/XChat/data/xchat.db';
		$this->load->database([
			'file'     => $dbFile,
			'type'     => 'sqlite',
			'driver'   => 'sqlite3',
			'debug'    => true
		]);
	}

	public function index() {
		if (!isset($_COOKIE['hash']) || !preg_match('/^\w{32}$/', $_COOKIE['hash'])) {
			$hash = md5(rand(10000, 9999).uniqid('', true));
		} else {
			$hash = $_COOKIE['hash'];
		}
		
		Http::setcookie('hash', $hash, time()+86400*30, '/xchat');

		$sth = $this->db->query("SELECT nickname FROM users WHERE hash='$hash'");
		$user = $sth->fetch();
		$nickname = $user ? $user['nickname'] : '';
		
		$this->load->view('xchat_index', compact('hash', 'nickname'));
	}

	public function getHistory() {
		$history = [];
		$sth = $this->db->query('SELECT id,nickname,`time`,msg,uid FROM messages ORDER BY id DESC LIMIT 100');
		
		while ($row = $sth->fetch()) {
			$row['time'] = date('Y-m-d H:i:s', $row['time']);
			$history[] = $row;
		}

		nvHeader("Cache-Control: max-age=0, no-cache, must-revalidate");
		echo json_encode(array_reverse($history), JSON_UNESCAPED_UNICODE);
	}
	
	public function checkDb() {
		print_r($this->db);
	}
    
}
