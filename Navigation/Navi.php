<?php
namespace Navigation;

use Navigation\Core\Router;

define('NAVI_SYSTEM_PATH', __DIR__);

class Navi {

	private static $config;

	/**
	 * @var Router
	 */
	private static $router;

	/**
	 * @param string $configFile System config file of Navi Framework
	 * @param \Workerman\Worker $worker
	 */
	public static function bootstrap($configFile, $worker) {
		if (!is_file($configFile)) {
			exit('No found config file: '.$configFile."\n");
		}

		//Load system config
		self::$config = include $configFile;

		include __DIR__.'/Core/Common.php';
		include __DIR__.'/Core/Interface.php';

		self::$router = new Router(self::$config['apps'], self::$config['routeMapManager']);
	}

	/**
	 * @param \Workerman\Connection\TcpConnection $conn
	 * @param array $data
	 */
	public static function request($conn, $data) {
		self::fixPathInfo();

		ob_start();

		try {
			$request = self::$router->parse();

			if (!$request) {
				nv404(); //Controller file not found
			}

			if (!class_exists($request['className'])) {
				nv404(); //Class not found
			}

			$instance = new $request['className'];
			$action = array_shift($request['params']);

			if (!$action) {
				$action = 'index';
			}

			print_r($request);

			if (is_callable(array(&$instance, $action))) {
				call_user_func_array(array(&$instance, $action), $request['params']);
			}


		} catch (\ExitException $e) {
			//$trace = $e->getTrace();
			//$exitWay = $trace[1];
		}

		$buffer = ob_get_clean();
		$conn->send($buffer);
	}

	private function fixPathInfo() {
		if ($_SERVER['REQUEST_URI'] == '') return;
		$q = strpos($_SERVER['REQUEST_URI'], '?');
		$_SERVER['PATH_INFO'] = $q !== false ? substr($_SERVER['REQUEST_URI'], 0, $q) : $_SERVER['REQUEST_URI'];
	}

}
