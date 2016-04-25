<?php

namespace Wide\Controller;

use Navigation\Database\Db;
use Navigation\Database\Util;
use Wide\Model\Bon;
use Wide\Model\Test as TestModel;

class Test extends \Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		echo 'This is test controller.<br />';

		if (class_exists('\\Wide\\Model\\Test')) {
			$obj =  \Wide\Model\Test::instance();
			echo $obj->iam();

			$obj2 = TestModel::instance();
			echo $obj2->iam();
		}
	}

	public function instance() {
		$user = instance('\\Wide\\Model\\Test')->iam();
		echo $user;
	}

	public function newWorld($a=1,$b=2) {
		echo 'Hello World in Test Controller.';
		echo '<br />';
		echo $a.'-'.$b;
		echo '<pre>';
		print_r(get_included_files());
		echo '</pre>';
	}

	public function ivkmodel() {
		$m = TestModel::instance();

		echo '<pre>';
		print_r($m);
		echo '</pre>';
	}

	public function config() {
		//$apps = $this->config->loadConfig();

		$this->config->load('peace');

		echo $this->config->get('k1');

		$this->config->load('peace', false, false, true);

		echo $this->config->get('k2');

		echo $this->config->get('viewFileExtension');
	}

	public function updateConfig() {
		$this->config->set('servlet', "just a test\nupdate in ".date('Y-m-d H:i:s'));

		//echo '---done---';
	}

	public function loader() {
		$this->load->import(array('mod/test', 'library/jovi'));

		//$this->load->showMaps();

		echo $this->test->iam();

		echo "<br />\n";

		echo $this->abc;

		$this->jovi->bon();

		//$this->jovi->cc();

		//echo $struct;

		echo 'Script is still running.';
	}

	public function printserver() {
		echo '<pre>';
		print_r($_SERVER);
		echo '</pre>';
	}

	public function input() {
		echo $this->input->ip();
		echo '<br />';
		echo $this->input->userAgent();
		echo '<br />';
		print_r($this->input->uri());
		echo '<br />';
	}

	public function routes($p='') {

	}

	public function s() {
		echo '<img src="http://127.0.0.1:8001/static/example.jpg" />';
	}

	public function db() {
		$db = new Db('dsn');
		$result = $db->query('SELECT * FROM text');

		$row = $result->all();

		$result->free();

		print_r($row);

		echo '<p>=====================================</p>';

		$parse = Util::parseDsn('sqlite: host=127.0.0.1; username=root; password=0000; port=3306; dbname=test');
		print_r($parse);
	}

	public function sqlite() {
		$db = new Db('local');

		/*
		$date = date('Y-m-d H:i:s ').rand(1000, 9999);
		$db->query("INSERT INTO text (text) VALUES('$date')");

		$result = $db->query("SELECT * FROM text ORDER BY id DESC LIMIT 1");

		$data = $result->fetch();
		*/

		$result = $db->query("SELECT * FROM text ORDER BY id ASC LIMIT 10");

		$data = $result->all();

		var_dump($db->getClientVersion());

		echo $db->dbType.'<br />';
		echo $db->driverName.'<br />';

		echo '<pre>';
		print_r($data);
		echo '</pre>';

		echo $result->columnCount();
	}

	public function modelInstance() {
		$m = instance('\\Wide\\Model\\Test');
		echo $m->iam();

		$b = \Wide\Model\Test::instance();
		echo $m->iam();

		print_r($this->__nvObjects);

		$bon = Bon::instance();
		$bon->test();
	}

	public function view() {
		$this->load->view('deep/dv');
	}

	public function gc() {
		echo gc_collect_cycles();
	}

}
