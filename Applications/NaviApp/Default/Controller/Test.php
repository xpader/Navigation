<?php

namespace Wide\Controller;

class Test extends \Controller {

	public function index() {
		echo 'This is test controller.<br />';

		if (class_exists('\\Wide\\Model\\Test')) {
			$obj = new \Wide\Model\Test();
			echo $obj->iam();

			$obj2 = new \Wide\Model\Test();
			echo $obj2->iam();
		}
	}

	public function newWorld($a=1,$b=2) {
		echo 'Hello World in Test Controller.';
		echo '<br />';
		echo $a.'-'.$b;
	}

	public function ivkmodel() {
		$m = new \Wide\Model\Test();

		echo '<pre>';
		print_r($m);
		echo '</pre>';
	}

	public function config() {
		//$apps = $this->config->loadConfig();

		var_dump($this->config->get('servlet'));

	}

	public function updateConfig() {
		$this->config->set('servlet', "just a test\nupdate in ".date('Y-m-d H:i:s'));
	}

	public function loader() {
		$this->load->import(['mod/test', 'library/jovi']);

		//$this->load->showMaps();

		echo $this->test->iam();

		echo "<br />\n";

		$this->jovi->bon();

		$this->jovi->cc();

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
	}

}
