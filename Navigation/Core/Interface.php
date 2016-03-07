<?php

use Navigation\Navi;

abstract class Base {

	public $config;
	public $router;

	public function __construct() {
		$this->config = Navi::getObject('config');
		$this->router = Navi::getObject('router');
	}

}

abstract class Controller extends Base {
}

abstract class Model extends Base {
}
