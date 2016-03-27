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

/**
 * Mysqli driver of MySQL database
 *
 * This is a driver adapter for database class
 *
 * @package Navigation\Database
 */
class DriverMysqli extends DriverInterface {

	public function connect($config) {
		$port = null;

		//Use dsn
		if (!empty($config['dsn'])) {
			$config += Util::parseDsn($config['dsn']);
		}

		if (strpos($config['host'], ':') !== false) {
			list($host, $port) = explode(':', $config['host']);
		} else {
			$host = $config['host'];

			if (!empty($config['port'])) {
				$port = $config['port'];
			}
		}

		$config['pconnect'] && $host = 'p:'.$host;

		$this->link = @mysqli_connect($host, $config['username'], $config['password'], $config['dbname'], $port);

		return (bool)$this->link;
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

	public function setCharset($charset, $collation='') {
		if ($collation) {
			return @mysqli_query($this->link, "SET NAMES '$charset' COLLATE '$collation'");
		} else {
			return mysqli_set_charset($this->link, $charset);
		}
	}

	public function query($sql) {
		return @mysqli_query($this->link, $sql);
	}

	public function insertId() {
		return mysqli_insert_id($this->link);
	}

	public function affectedRows() {
		return mysqli_affected_rows($this->link);
	}

	public function begin() { return PHP_VERSION_ID >= 50500 ? mysqli_begin_transaction($this->link) : $this->query('BEGIN'); }

	public function commit() { return mysqli_commit($this->link); }

	public function rollback() { return mysqli_rollback($this->link); }

	public function getServerVersion() { return mysqli_get_server_info($this->link); }

	public function getClientVersion() { return mysqli_get_client_info(); }

	public function errorCode() {
		return $this->link ? mysqli_errno($this->link) : mysqli_connect_errno();
	}

	public function errorMessage() {
		return $this->link ? mysqli_error($this->link) : mysqli_connect_error();
	}

}

class ResultMysqli extends ResultInterface {

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
