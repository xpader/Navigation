<?php

namespace Navigation\Core;

class Router {

	private $routeMap = array();
	private $hostMap = array();
	private $activeApps = array();
	private $currentApp;

	/**
	 * @param array $apps
	 * @param string $mapManager
	 */
	public function __construct($apps, $mapManager) {
		if (count($apps) == 0 || !is_array($apps)) {
			exit("No apps in config!\n");
		}

		$this->fetchApps($apps);
	}

	/**
	 * Prase request controller
	 *
	 * @return array|bool
	 */
	public function parse() {
		//Match app from server name
		$serverName = strtolower($_SERVER['SERVER_NAME']);
		$index = null;

		if (isset($this->hostMap[$serverName])) {
			$index = $this->hostMap[$serverName];
		} elseif (isset($this->hostMap['*'])) {
			$index = $this->hostMap['*'];
		} elseif (isset($this->hostMap['localhost'])) {
			$index = $this->hostMap['localhost'];
		} else {
			nv404();
		}

		$app = $this->activeApps[$index];

		//Export the request uri
		$uri = isset($_SERVER['PATH_INFO']) ? ltrim($_SERVER['PATH_INFO'], '/') : '';
		$URI = $uri ? explode('/', $uri) : array();

		//Find controller
		$path = $app['path'].DIRECTORY_SEPARATOR.'Controller';
		$pl = strlen($path);
		$controller = '';
		$params = array();

		foreach ($URI as $i => $seg) {
			$seg = ucfirst($seg);
			$path .= DIRECTORY_SEPARATOR.$seg;

			if (is_file($path.'.php')) {
				$controller = $path;
				$params = array_slice($URI, $i+1);
				break;
			}
		}

		if ($controller == '' && is_dir($path) && is_file($path.DIRECTORY_SEPARATOR.'Index.php')) {
			$controller = $path.DIRECTORY_SEPARATOR.'Index';
		}

		if ($controller == '') {
			return false;
		}

		$className = '\\'.$app['namespace'].'\\Controller'.str_replace(DIRECTORY_SEPARATOR, '\\', substr($controller, $pl));
		//$controller .= '.php';

		$this->currentApp = $index;

		return array(
			'app' => $index,
			'className' => $className,
			'controller' => $controller,
			'params' => $params
		);
	}

	public function fetchAction($object, $params) {

	}

	/**
	 * Fetch apps config to initialize
	 *
	 * @param array $applications
	 */
	private function fetchApps($applications) {
		$enabledCount = 0;

		$map = array();

		foreach ($applications as $i => $app) {
			if (!$app['enabled']) continue;

			//Bind server name
			if (!is_array($app['serverName'])) {
				$app['serverName'] = array($app['serverName']);
			}

			foreach ($app['serverName'] as $name) {
				$name = strtolower($name);
				if (isset($map[$name])) continue;
				$map[$name] = $i;
			}

			//Register App
			$this->activeApps[$i] = $app;

			++$enabledCount;
		}

		if ($enabledCount == 0) {
			exit("No enabled app was active!\n");
		}

		$this->hostMap = $map;
	}

	public function getActiveApps() {
		return $this->activeApps;
	}

	public function getApp($index) {
		return isset($this->activeApps[$index]) ? $this->activeApps[$index] : null;
	}

	public function getCurrentApp() {
		return $this->currentApp;
	}

}
