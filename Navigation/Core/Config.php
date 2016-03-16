<?php

namespace Navigation\Core;

class Config {

	private static $initialized = false;
	private static $activeApps = array();
	private static $appConfigMaps = array();

	private $configs;
	private $appIndex;

	/**
	 * Construct for controller
	 *
	 * @param string $appIndex Current app index
	 */
	public function __construct($appIndex) {
		$this->appIndex = $appIndex;
		$this->configs =& self::$appConfigMaps[$appIndex];
	}

	/**
	 * Preset the envrionment of the app
	 *
	 * @param array $globalConfig
	 */
	public static function initialize($globalConfig) {
		if (self::$initialized) return;

		self::fetchActiveApps($globalConfig['apps'], $globalConfig['defaultEnvrionment']);
		self::$initialized = true;
	}

	/**
	 * Fetch apps config to initialize
	 *
	 * @param array $applications
	 * @param string $defaultEnv
	 */
	private static function fetchActiveApps($applications, $defaultEnv) {
		$enabledCount = 0;

		foreach ($applications as $index => $app) {
			if (!$app['enabled']) continue;

			//Bind server name
			if (!is_array($app['serverName'])) {
				$app['serverName'] = array($app['serverName']);
			}

			$app['envrionment'] = empty($app['envrionment']) ? $defaultEnv : $app['envrionment'];

			//Register App
			self::$activeApps[$index] = $app;

			//Register configs
			self::$appConfigMaps[$index] = array(
				'config' => self::sload('config', $index),
				'routes' => self::sload('routes', $index)
			);

			++$enabledCount;
		}

		if ($enabledCount == 0) {
			exit("No enabled apps was active!\n");
		}
	}

	public static function getActiveApps() {
		return self::$activeApps;
	}

	public static function getApp($index) {
		return isset(self::$activeApps[$index]) ? self::$activeApps[$index] : null;
	}

	/**
	 * Load config file and return value
	 *
	 * This method try to find and load special envrionment config,
	 * if envrionment config not found, try to load common config,
	 * else will return null.
	 *
	 * @param string $name Config name
	 * @param string $app App index
	 * @return mixed|null
	 */
	protected static function sload($name, $app) {
		$appConf = self::$activeApps[$app];
		$confDir = $appConf['path'].DIRECTORY_SEPARATOR.'Config'.DIRECTORY_SEPARATOR;

		$fetch = array(
			$confDir.$appConf['envrionment'].DIRECTORY_SEPARATOR.$name.'.php',
			$confDir.$name.'.php'
		);

		foreach ($fetch as $configFile) {
			if (is_file($configFile)) {
				return include $configFile;
			}
		}

		return null;
	}

	/**
	 * Get an exists config
	 *
	 * To call this method must after static initialized.
	 *
	 * @param string $config Config name
	 * @param string $appIndex
	 * @return array|null
	 */
	public static function item($config, $appIndex) {
		return isset(self::$appConfigMaps[$appIndex][$config]) ?
			self::$appConfigMaps[$appIndex][$config]: null;
	}

	/**
	 * Load Config
	 *
	 * This will re-read the config file
	 *
	 * @param string $name
	 * @param bool $useSection Is config in a stand alone space, if true, you must call get use $config
	 * @param bool $return Return config array
	 * @return array|null
	 */
	public function load($name, $useSection=false, $return=false) {
		$config = self::sload($name, $this->appIndex);

		if ($config === null) {
			nvExit("Can not found config: $name.\n");
		}

		if ($useSection) {
			self::$appConfigMaps[$this->appIndex][$name] = $config;
		} else {
			self::$appConfigMaps[$this->appIndex][$name] += $config;
		}

		if ($return) return $config;
	}

	/**
	 * Get config value
	 *
	 * @param string $key
	 * @param string $section If config load to separate space, must set this value to separate name
	 * @return mixed
	 */
	public function get($key, $section='config') {
		if (!isset($this->configs[$section])) {
			$this->load($section, true);
		}

		return isset($this->configs[$section][$key]) ?
			$this->configs[$section][$key] : null;
	}

	/**
	 * Set a config value
	 *
	 * The value will be affected until worker process restart.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $section If set to separate space, this value must set to separate name
	 */
	public function set($key, $value, $section='config') {
		if (!isset($this->configs[$section])) {
			$this->configs[$section] = array();
		}

		$this->configs[$section][$key] = $value;
	}

	public function getCurrentApp() {
		return self::getApp($this->appIndex);
	}

}
