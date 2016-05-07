<?php

namespace Wide\Controller\Test;

use Navigation\Database\Util;

class Database extends \Controller {

	public function index() {
		$this->load->database();

		$result = $this->db->query('SELECT * FROM text');

		$row = $result->all();

		//$result->free();

		print_r($row);

		echo '<p>=====================================</p>';
	}

	public function one() {
		echo '<b>One</b>';
	}

}
