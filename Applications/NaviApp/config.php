<?php
//Navigation Framework system config

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

	/**
	 * Apps Config
	 */
	'apps' => array(
		'default' => array(
			'name' => 'Default Navi App',
			'enabled' => true,
			'namespace' => 'Wide',
			'path' => __DIR__.DIRECTORY_SEPARATOR.'Default',
			'serverName' => '*'
		),
		'test' => array(
			'name' => 'Test Navi App',
			'enabled' => false,
			'namespace' => 'NaviTest',
			'path' => __DIR__.DIRECTORY_SEPARATOR.'Test',
			'serverName' => ['localhost', 't.pp.cn']
		)
	)
);
