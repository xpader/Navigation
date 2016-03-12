<?php

namespace Navigation\Core;

class Config {

	private static $initialized = false;
	private static $appMaps = array();

	private $configs;
	private $appIndex;

	/**
	 * Construct for controller
	 *
	 * @param string $appIndex Current app index
	 */
	public function __construct($appIndex) {
		$this->appIndex = $appIndex;
		$this->configs =& self::$appMaps[$appIndex]['configs'];
	}

	/**
	 * Preset the envrionment of the app
	 *
	 * @param string $defaultEnv Default envrionment set
	 * @param array $activeApps
	 */
	public static function initialize($defaultEnv, $activeApps) {
		if (self::$initialized) {
			return;
		}

		foreach ($activeApps as $index => $app) {
			self::$appMaps[$index] = array(
				'envrionment' => empty($app['envrionment']) ? $defaultEnv : $app['envrionment'],
				'path' => $app['path'],
				'configs' => array()
			);

			//Must after $appMaps has be build
			self::$appMaps[$index]['configs']['config'] = self::sload('config', $index);
		}

		self::$initialized = true;
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
		$appConf = self::$appMaps[$app];
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
				self::$appMaps[$this->appIndex]['configs'][$name] = $config;
			} else {
				self::$appMaps[$this->appIndex]['configs'][$name] += $config;
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

}
