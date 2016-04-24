<?php

use Navigation\Navi;
use Navigation\Core\Config;
use Navigation\Core\Loader;

/**
 * Class Controller
 *
 * @property Navigation\Core\Config config
 * @property Navigation\Core\Router router
 */
abstract class Controller {

	private static $instance;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * @var Navigation\Core\Router
	 */
	public $router;

	/**
	 * @var Navigation\Core\Input
	 */
	public $input;

	/**
	 * @var Loader
	 */
	public $load;

	/**
	 * Instanced objects array
	 *
	 * The object must save in dynamic controller
	 * That when request finished, the object can be collection with controller
	 *
	 * @var array
	 */
	public $__nvObjects = array();

	public function __construct() {
		self::$instance =& $this;

		$this->router = Navi::getObject('router');
		$appIndex = $this->router->getCurrentAppIndex();
		$this->config = new Config($appIndex);

		$this->load = new Loader();
		$this->input = Navi::getObject('input');
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
		//Fatal error: Cannot access property
		if (isset($this->$name)) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			nvCallError("Cannot access (protected, private) property: {$trace[1]['class']}::\$$name", E_USER_ERROR);
			return null;
		}

		//Load registered object
		if ($this->load->isObjectRegistered($name)) {
			$app = $this->config->getCurrentApp();
			$objInfo = $this->load->getRegisteredObject($name);

			$fetchClasses = array();

			if ($objInfo !== null) {
				if ($objInfo[0] !== 0) {
					$fetchClasses[] = $app['namespace'] . '\\' . $objInfo[1];

					//if its library, then try to load from Navigation\Library
					if ($objInfo[0] == 1) {
						$fetchClasses[] = 'Navigation\\'.$objInfo[1];
					}
				}
			}

			if ($fetchClasses) {
				foreach ($fetchClasses as $className) {
					if (class_exists($className)) {
						$this->$name = new $className;
						return $this->$name;
					}
				}
			}
		}

		//Notice error: Trigger right undefiend error
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		nvCallError("Undefined property: {$trace[1]['class']}::\$$name", E_USER_NOTICE);

		return null;
	}

}

abstract class Model {

	public function __get($name) {
		return getInstance()->$name;
	}

	/**
	 * Return Single Instance
	 *
	 * @return $this
	 */
	public static function instance() {
		return instance(get_called_class());
	}

}
