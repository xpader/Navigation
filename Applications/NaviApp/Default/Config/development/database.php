<?php

return array(
	'default_config' => 'default',

	'default' => array(
		'dsn'      => '',
		'host'     => '127.0.0.1',
		'username' => 'root',
		'password' => '0000',
		'dbname'   => 'test',
		'tbprefix' => '',
		'pconnect' => true,
		'charset'  => 'gbk',
		'collate'  => 'gbk_chinese_ci',
		'type'     => 'mysql',
		'driver'   => 'pdo',
		'debug'    => true
	),

	'local' => array(
		'dsn'      => '',
		'file'     => '/Development/www/test.db',
		'type'     => 'sqlite',
		'driver'   => 'pdo',
		'debug'    => true
	)
);
