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

		print_r($m);
	}

	public function config() {
		echo 'Hello World in Test<br />'.date('Y-m-d H:i:s')."<br />";

		echo $this->router->getCurrentApp();
	}

	public function loader() {
		$this->config->loadConfig();

		$this->load->import(['mod/test', 'library/jovi']);

		$this->load->showMaps();

		echo $this->test->iam();

		$this->jovi->bon();

		echo 'Script is still running.';
	}

}
