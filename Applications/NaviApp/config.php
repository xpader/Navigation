<?php
/**
 * Navigation Framework system config
 */
define('NAVI_APPS_PATH', __DIR__.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR);

return array(
	/**
	 * Set how to load route map for request
	 *
	 * preload:
	 * Scan all controller to cache on bootstrap, every request will match controller in this map cache.
	 *
	 * dynamic:
	 * Not scan controller on bootstrap. When request coming, try to find in map cache, if not in cache,
	 * will scan controller directory and cache it.
	 *
	 * none:
	 * Not use map cache, scan controller directory every reqeust.
	 */
	'routeMapManager' => 'none',

	'defaultEnvrionment' => 'development',

	/**
	 * Apps Config
	 */
	'apps' => array(
		'default' => array(
			'name' => 'Default Navi App',
			'enabled' => true,
			'namespace' => 'Wide',
			'path' => NAVI_APPS_PATH.'default',
			'serverName' => '*',
			'envrionment' => 'development'
		),
		'test' => array(
			'name' => 'My App',
			'enabled' => false,
			'namespace' => 'My',
			'path' => NAVI_APPS_PATH.'myapp',
			'serverName' => array('localhost', 'myapp.navi'),
			'envrionment' => 'development'
		)
	)
);
