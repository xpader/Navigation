<?php
namespace Navigation;

use Navigation\Core\Router;
use Navigation\Core\Config;
use Navigation\Core\Input;

define('NAVI_SYSTEM_PATH', __DIR__);

class Navi {

	const VERSION = '0.1.0-alpha';

	private static $activeApps;

	/**
	 * @var Router
	 */
	private static $router;

	/**
	 * @var Input
	 */
	private static $input;

	private static $instance;

	/**
	 * Initialize framework and bootstrap
	 *
	 * @param string $configFile System config file of Navi Framework
	 * @param \Workerman\Worker $worker
	 */
	public static function bootstrap($configFile, $worker) {
		if (!is_file($configFile)) {
			exit('No found config file: '.$configFile."\n");
		}

		$coreDir = NAVI_SYSTEM_PATH.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR;

		//Load common helper
		include $coreDir.'common.php';

		//Load application interface
		include $coreDir.'Interface.php';

		//Load constant define
		include $coreDir.'constant.php';

		//Load system config
		$config = include $configFile;

		//Register autoloads
		spl_autoload_register('\Navigation\Navi::loadClass');

		//Initialize config
		Config::initialize($config);
		self::$activeApps = Config::getActiveApps();

		//Initialize router
		self::$router = new Router(self::$activeApps, $config['routeMapManager']);

		//Register error handler
		set_error_handler('_nvErrorHandler');
		//set_exception_handler('_nvExceptionHandler');
		//register_shutdown_function('_nvShutdownHandler');
	}

	/**
	 * Get object in Navi
	 *
	 * @param $name
	 * @return Router
	 */
	public static function getObject($name) {
		return isset(self::$$name) ? self::$$name : false;
	}

	/**
	 * Set controller instance
	 *
	 * @param \Controller $instance
	 * @throws \ExitException
	 */
	public static function setInstance(&$instance) {
		if ($instance instanceof \Controller) {
			self::$instance =& $instance;
		} else {
			nvExit("Navi Error: Not a valid controller instance.\n");
		}
	}

	/**
	 * Process request and response
	 *
	 * @param \Workerman\Connection\TcpConnection $conn
	 * @param array $data
	 */
	public static function request($conn, $data) {
		self::fixPathInfo();

		$statusCode = 0;

		ob_start();

		try {
			$uri = self::$router->parse();

			if (!$uri) {
				nv404(0); //Controller file not found
			}

			if (!class_exists($uri['className'])) {
				nv404(1); //Class not found
			}

			//Instance input before controller
			self::$input = new Input($uri, $conn);

			$instance = new $uri['className'];
			$action = !empty($uri['params'][0]) ? $uri['params'][0] : 'index';

			if (is_callable(array($instance, $action))) {
				unset($uri['params'][0]);
			} elseif (is_callable(array($instance, '_redirect'))) {
				$action = '_redirect';
			} else {
				nv404(2); //Action not found
			}

			call_user_func_array(array($instance, $action), $uri['params']);

		} catch (\ExitException $e) {
			//$trace = $e->getTrace();
			//$exitWay = $trace[1];

			//RAW_OUTPUT_BREAK = 5
			$code = $e->getCode();
			$code == 5 && $statusCode = $code;
		}

		//make sure input object been destory
		self::$input = null;

		//make sure static cycle been destory
		unset($instance);
		self::$instance = null;

		if ($statusCode === 0) {
			$buffer = ob_get_contents();
			ob_end_clean();
			$conn->send($buffer);
		} else {
			ob_end_flush();
		}
	}

	/**
	 * Autoloader
	 *
	 * @param $name
	 * @return bool
	 */
	public static function loadClass($name) {
		static $spaces = null;

		//Collect app namespaces
		if ($spaces === null) {
			$spaces = array();

			foreach (self::$activeApps as $i => $row) {
				$spaces[$row['namespace']] = $row['path'];
			}
		}

		$arr = explode('\\', $name);
		$ns = array_shift($arr);

		//Must have a namespace
		if (count($arr) == 0) {
			return false;
		}

		if ($ns == 'Navigation') {
			$filename = NAVI_SYSTEM_PATH . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $arr) . '.php';
		} elseif (isset($spaces[$ns])) {
			$filename = $spaces[$ns] . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $arr) . '.php';
		}

		if (isset($filename) && is_file($filename)) {
			include $filename;
			return class_exists($name, false);
		}

		return false;
	}

	/**
	 * Fix path_info for $_SERVER in Workerman
	 */
	private static function fixPathInfo() {
		if ($_SERVER['REQUEST_URI'] == '') return;
		$q = strpos($_SERVER['REQUEST_URI'], '?');
		$_SERVER['PATH_INFO'] = $q !== false ? substr($_SERVER['REQUEST_URI'], 0, $q) : $_SERVER['REQUEST_URI'];
	}

}
