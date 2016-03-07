<?php
namespace Navigation;

use Navigation\Core\Router;

define('NAVI_SYSTEM_PATH', __DIR__);

class Navi {

	private static $config;
	private static $activeApps;

	/**
	 * @var Router
	 */
	private static $router;

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

		//Load system config
		self::$config = include $configFile;

		//Register autoloads
		spl_autoload_register('\Navigation\Navi::loadClass');

		//Include common files
		include NAVI_SYSTEM_PATH.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'Common.php';
		include NAVI_SYSTEM_PATH.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'Interface.php';

		//Initialize router
		self::$router = new Router(self::$config['apps'], self::$config['routeMapManager']);
		self::$activeApps = self::$router->getActiveApps();
	}

	/**
	 * Process request and response
	 *
	 * @param \Workerman\Connection\TcpConnection $conn
	 * @param array $data
	 */
	public static function request($conn, $data) {
		self::fixPathInfo();

		ob_start();

		try {
			$request = self::$router->parse();

			if (!$request) {
				nv404(0); //Controller file not found
			}

			if (!class_exists($request['className'])) {
				nv404(1); //Class not found
			}

			$action = !empty($request['params'][0]) ? $request['params'][0] : 'index';
			$instance = new $request['className'];

			if (is_callable(array($instance, $action))) {
				unset($request['params'][0]);
			} elseif (is_callable(array($instance, '_redirect'))) {
				$action = '_redirect';
			} else {
				nv404(2); //Action not found
			}

			call_user_func_array(array($instance, $action), $request['params']);

		} catch (\ExitException $e) {
			//$trace = $e->getTrace();
			//$exitWay = $trace[1];
		}

		$buffer = ob_get_contents();
		ob_end_clean();

		$conn->send($buffer);
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
	private function fixPathInfo() {
		if ($_SERVER['REQUEST_URI'] == '') return;
		$q = strpos($_SERVER['REQUEST_URI'], '?');
		$_SERVER['PATH_INFO'] = $q !== false ? substr($_SERVER['REQUEST_URI'], 0, $q) : $_SERVER['REQUEST_URI'];
	}

}
