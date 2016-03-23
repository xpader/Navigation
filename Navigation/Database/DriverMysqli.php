<?php
/**
 * Navigation PHP Framework
 *
 * @package Navigation
 * @author pader
 * @copyright Copyright (c) 2016, VGOT.NET
 * @link http://git.oschina.net/pader/Navigation
 */

namespace Navigation\Database;

use \mysqli;
use \mysqli_result;

class DriverMysqli extends Db {

	protected $driverName = 'mysqli';

	protected function realConnect($config) {
		$port = null;

		if (strpos($config['host'], ':') !== false) {
			list($host, $port) = explode(':', $config['host']);
		} else {
			$host = $config['host'];
			if (isset($config['port'])) {
				$port = $config['port'];
			}
		}

		$config['pconnect'] && $host = 'p:'.$host;

		$this->link = @mysqli_connect($host, $config['username'], $config['password'], $config['dbname'], $port);

		if (!$this->link) {
			return false;
		}

		//Set chars
		if (isset($config['charset']) && $config['charset'] != '') $this->setChars($config['charset'],$config['collat']);

		return true;
	}

	public function close() {
		if ($this->link !== null) {
			mysqli_close($this->link);
			$this->link = null;
		}
	}

	public function ping() {
		return ($this->link instanceof mysqli) && mysqli_ping($this->link);
	}

	public function execute($sql) {
		return @mysqli_query($this->link, $sql);
	}

	public function lastId() {
		return mysqli_insert_id($this->link);
	}

	public function affectedRows() {
		return mysqli_affected_rows($this->link);
	}

	public function escapeStr() {

	}

	/**
	 * Begin Transaction
	 *
	 * @return bool
	 */
	public function begin() { return PHP_VERSION_ID >= 50500 ? mysqli_begin_transaction($this->link) : $this->query('BEGIN'); }

	/**
	 * Commit Transaction
	 *
	 * @return bool
	 */
	public function commit() { return mysqli_commit($this->link); }

	/**
	 * Rollback Transaction
	 *
	 * @return bool
	 */
	public function rollback() { return mysqli_rollback($this->link); }

	public function getServerVersion() { return mysqli_get_server_info($this->link); }

	public function getClientVersion() { return mysqli_get_client_info(); }
	
	/**
	 * 字符集设置
	 *
	 * @param string $charset
	 * @param string $collation
	 * @return mysqli_result
	 */
	public function setChars($charset, $collation='') {
		$set = "SET NAMES '$charset'";
		$collation != '' && $set .= " COLLATE '$collation'";

		return @mysqli_query($this->link, $set);
	}

	/**
	 * @return int
	 */
	public function errorCode() {
		return $this->link ? mysqli_errno($this->link) : mysqli_connect_errno();
	}

	/**
	 * @return string
	 */
	public function errorMessage() {
		return $this->link ? mysqli_error($this->link) : mysqli_connect_error();
	}

}

class MysqliResult extends ResultInterface {

	public function fetch($type=DB_ASSOC) {
		return mysqli_fetch_array($this->result, $type);
	}

	public function all($type=DB_ASSOC) {
		return mysqli_fetch_all($this->result, $type);
	}

	public function rowCount() { return mysqli_num_rows($this->result); }

	public function columnCount() { return mysqli_num_fields($this->result); }

	public function free() { mysqli_free_result($this->result); }

}
