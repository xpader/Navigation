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
				'config' => self::sload('config', $index)
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
	 * Load Config
	 *
	 * This will re-read the config file
	 *
	 * @param string $name
	 * @param bool $separate is config in a stand alone space, if true, you must call get use $config
	 */
	public function load($name, $separate=false) {
		$config = self::sload($name, $this->appIndex);

		if ($config !== null) {
			if ($separate) {
				self::$appConfigMaps[$this->appIndex][$name] = $config;
			} else {
				self::$appConfigMaps[$this->appIndex][$name] += $config;
			}
		}

		nvExit("Can not found config: $name.\n");
	}

	/**
	 * Get config value
	 *
	 * @param string $key
	 * @param string $config If config load to separate space, must set this value to separate name
	 * @return mixed
	 */
	public function get($key, $config='') {
		$config == '' && $config = 'config';

		if (!isset($this->configs[$config])) {
			$this->load($config);
		}

		return isset($this->configs[$config][$key]) ?
			$this->configs[$config][$key] : null;
	}

	/**
	 * Set a config value
	 *
	 * The value will be affected until worker process restart.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param string $config If set to separate space, this value must set to separate name
	 */
	public function set($key, $value, $config='') {
		$config == '' && $config = 'config';

		if (!isset($this->configs[$config])) {
			$this->configs[$config] = array();
		}

		$this->configs[$config][$key] = $value;
	}

	public function getCurrentApp() {
		return self::getApp($this->appIndex);
	}

}
