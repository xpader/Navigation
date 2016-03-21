<?php

namespace Navigation\Database;

abstract class AdapterInterface {

	/**
	 * Driver connection
	 *
	 * @var
	 */
	public $link;

	/**
	 * Database type name
	 *
	 * @var string
	 */
	public $dbType;

	/**
	 * Is database is an embedded db
	 *
	 * Set to true if its embedded database, like sqlite ..
	 *
	 * @var bool
	 */
	public $embedded = false;

	/**
	 * Last query SQL string
	 *
	 * @var string
	 */
	public $lastQuery = '';

	abstract public function connect($config);
	abstract public function ping();
	abstract public function query($sql);
	abstract public function fetch($result);
	abstract public function lastId();

	abstract public function begin();
	abstract public function commit();
	abstract public function rollback();

	abstract public function getServerVersion();
	abstract public function getClientVersion();

	abstract public function errorCode();
	abstract public function errorMessage();

}

interface QueryResult {

}
