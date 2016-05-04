<?php

namespace Navigation\Core;

class Router {

	private $routeMap = array();
	private $hostMap = array();
	private $activeApps = array();
	private $currentApp;

	/**
	 * Initialize on app startup
	 *
	 * @param array $activeApps
	 * @param string $mapManager
	 */
	public function __construct($activeApps, $mapManager) {
		if (count($activeApps) == 0 || !is_array($activeApps)) {
			exit("No apps in config!\n");
		}

		$this->activeApps = $activeApps;

		//fetch server name to host maps
		foreach ($activeApps as $i => $app) {
			foreach ($app['serverName'] as $name) {
				$name = strtolower($name);
				//ignore same server name
				if (isset($this->hostMap[$name])) continue;
				$this->hostMap[$name] = $i;
			}

			$this->routeMap[$i] = Config::item('routes', $i);
		}
	}

	/**
	 * Explain and translate the true uri
	 *
	 * @return mixed|string
	 */
	protected function exportURI() {
		//export the request uri
		$sourceUri = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

		//translate routes
		$realUri = $sourceUri;
		$routes = $this->routeMap[$this->currentApp];

		if ($routes) {
			foreach ($routes as $exp => $route) {
				$exp = '#^'.$exp.'$#';
				if (preg_match($exp, $sourceUri)) {
					$realUri = preg_replace($exp, $route, $sourceUri);
					break;
				} elseif ($sourceUri == $exp) {
					break;
				}
			}
		}

		return array(
			'source' => $sourceUri,
			'real' => $realUri,
			'array' => $realUri ? explode('/', $realUri) : array()
		);
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
		$this->currentApp = $index;

		//Export the request uri
		$uri = $this->exportURI();

		//Find controller
		$path = $app['path'].DIRECTORY_SEPARATOR.'Controller';
		$pl = strlen($path);
		$controller = '';
		$params = array();

		foreach ($uri['array'] as $i => $seg) {
			$seg = ucfirst($seg);
			$path .= DIRECTORY_SEPARATOR.$seg;

			if (is_file($path.'.php')) {
				$controller = $path;
				$params = array_slice($uri['array'], $i+1);
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

		$uri['app'] = $index;
		$uri['className'] = $className;
		$uri['controller'] = $controller;
		$uri['params'] = $params;

		return $uri;
	}

	public function fetchAction($object, $params) {

	}

	public function getCurrentAppIndex() {
		return $this->currentApp;
	}

}
