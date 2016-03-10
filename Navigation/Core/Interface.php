<?php

use Navigation\Navi;
use Navigation\Core\Loader;

/**
 * Class Controller
 *
 * @property \Navigation\Core\Config config
 * @property \Navigation\Core\Router router
 */
abstract class Controller {

	private static $instance;

	/**
	 * @var \Navigation\Core\Config
	 */
	public $config;

	/**
	 * @var Loader
	 */
	public $load;

	public function __construct() {
		self::$instance =& $this;

		$this->config = Navi::getObject('config');

		$this->load = new Loader(array('config', 'load'));
	}

	/**
	 * Get Controller instance
	 *
	 * @return Controller
	 */
	public static function &getInstance() {
		return self::$instance;
	}

	/**
	 * Objects getter proxy
	 *
	 * @param $name
	 * @return null
	 */
	public function __get($name) {
		if (isset($this->$name)) {
			if (substr($name, 0, 1) != '_') {
				return $this->$name;
			} else {
				//property is not callable error
				trigger_error("Property $name is not allow yet.");
			}
		}

		//Load registered object
		if ($this->load->isObjectRegistered($name)) {
			$app = Navi::getObject('router')->getCurrentApp();
			$map = $this->load->getRegisteredObject($name);

			$checkClasses = array();

			if ($map !== null) {
				if ($map[0] !== 0) {
					$checkClasses[] = $app['namespace'] . '\\' . $map[1];

					if ($map[0] == 1) {
						$checkClasses[] = 'Navigation\\'.$map[1];
					}
				}
			}

			if ($checkClasses) {
				foreach ($checkClasses as $className) {
					if (class_exists($className)) {
						$this->$name = new $className;
						return $this->$name;
					}
				}
			}
		}

		//Trigger right undefiend error
		$trace = debug_backtrace();
		$className = get_class($trace[0]['object']);
		$error = "__NAVI_ERROR__\nUndefined property: $className::\$$name\n{$trace[0]['file']}\n{$trace[0]['line']}";

		unset($trace);

		trigger_error($error, E_USER_NOTICE);

		return null;
	}

}

abstract class Model {

	public function __get($name) {
		return getInstance()->$name;
	}

}
