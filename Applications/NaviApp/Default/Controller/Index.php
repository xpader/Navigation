<?php

namespace Wide\Controller;

class Index extends \Controller {

	public function index() {
		echo 'Hello World<br />'.date('Y-m-d H:i:s');
	}

}