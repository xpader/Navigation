<?php

namespace Navigation\Database;

abstract class DriverInterface {

	/**
	 * Is database is an embedded db
	 *
	 * Set to true if its embedded database, like sqlite ..
	 *
	 * @var bool
	 */
	public $embedded = false;

	/**
	 * Driver connection
	 */
	public $link;

	/**
	 * Real connect to database
	 *
	 * @param array $config
	 * @return bool
	 */
	abstract public function connect($config);

	/**
	 * Send a ping & Keep alive
	 *
	 * @return mixed
	 */
	abstract public function ping();

	/**
	 * Close database connection
	 *
	 * @return void
	 */
	abstract public function close();

	/**
	 * Sets the default client character set and collation
	 *
	 * @param string $charset
	 * @param string $collate
	 * @return bool
	 */
	abstract public function setCharset($charset, $collate='');

	/**
	 * Execute a query and return
	 *
	 * @param string $sql
	 * @return mixed
	 */
	abstract public function query($sql);

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return int
	 */
	abstract public function insertId();

	/**
	 * Get the number of affected rows in previous operation
	 *
	 * @return int
	 */
	abstract public function affectedRows();

	/**
	 * Begin a transaction
	 *
	 * @return bool
	 */
	abstract public function begin();

	/**
	 * Commit transaction
	 *
	 * @return bool
	 */
	abstract public function commit();

	/**
	 * Rollback transaction
	 *
	 * @return bool
	 */
	abstract public function rollback();

	/**
	 * Get database server version
	 *
	 * @return string
	 */
	abstract public function getServerVersion();

	/**
	 * Get this database client version
	 *
	 * @return string
	 */
	abstract public function getClientVersion();

	/**
	 * Get error code of last error
	 *
	 * @return int
	 */
	abstract public function errorCode();

	/**
	 * Get error message of last error
	 *
	 * @return string
	 */
	abstract public function errorMessage();

}

abstract class ResultInterface {

	protected $result;

	/**
	 * @param resource|\mysqli_result|\PDOStatement $result
	 */
	public function __construct($result) {
		$this->result = $result;
	}

	/**
	 * Fetch Row From DB Result
	 *
	 * @param int $type DB_ASSOC|DB_NUM
	 * @return array|null
	 */
	abstract public function fetch($type=DB_ASSOC);

	/**
	 * Fetch and get all query result as an associative array
	 *
	 * @param int $type
	 * @return array|null
	 */
	abstract public function all($type=DB_ASSOC);

	/**
	 * Get a column value from row
	 *
	 * This will move the cursor to next row
	 *
	 * @param int $columnNumber
	 * @return mixed
	 */
	public function column($columnNumber=0) {
		$row = $this->fetch(DB_NUM);
		return $row[$columnNumber];
	}

	/**
	 * Return number of rows in result set
	 *
	 * @return int
	 */
	abstract public function rowCount();

	/**
	 * Return number of fields in result set
	 *
	 * @return int
	 */
	abstract public function columnCount();

	/**
	 * Frees the memory associated with a result
	 *
	 * Under normal circumstances no need to use
	 *
	 * @return void
	 */
	abstract public function free();

}