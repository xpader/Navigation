<?php

namespace Wide\Controller;

class XChat extends \Controller {
	
	public function index() {
		$this->load->view('xchat_index');
	}

	public function getHistory() {
		$dbFile = RUN_DIR.'/Applications/XChat/data/xchat.db';
		$this->load->database([
			'file'     => $dbFile,
			'type'     => 'sqlite',
			'driver'   => 'sqlite3',
			'debug'    => true
		]);
		
		$history = [];
		$sth = $this->db->query('SELECT id,nickname,`time`,msg FROM messages ORDER BY id DESC LIMIT 100');
		
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
