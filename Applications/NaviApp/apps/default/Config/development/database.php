<?php

return array(
	'default_config' => 'default',

	'default' => array(
		'dsn'      => null,
		'host'     => '127.0.0.1',
		'username' => 'root',
		'password' => '0000',
		'dbname'   => 'test',
		'tbprefix' => '',
		'pconnect' => false,
		'charset'  => 'gbk',
		'collate'  => 'gbk_chinese_ci',
		'type'     => 'mysql',
		'driver'   => 'mysqli',
		'debug'    => true
	),

	'dsn' => array(
		'dsn'      => 'mysql:host=127.0.0.1;dbname=test;charset=gbk',
		'username' => 'root',
		'password' => '0000',
		'tbprefix' => '',
		'pconnect' => true,
		'collate'  => 'gbk_chinese_ci',
		'type'     => 'mysql',
		'driver'   => 'pdo',
		'debug'    => true
	),

	'local' => array(
		'file'     => '/Development/www/test.db',
		'type'     => 'sqlite',
		'driver'   => 'pdo',
		'debug'    => true
	)
);
