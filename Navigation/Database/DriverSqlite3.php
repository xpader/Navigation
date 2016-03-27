<?php

namespace Navigation\Database;

use SQLite3;
use SQLite3Result;

/**
 * Sqlite3 driver of SQLite database
 *
 * This is a driver adapter for database class
 *
 * @package Navigation\Database
 * @property SQLite3 link
 */
class DriverSqlite3 extends DriverInterface {

	/**
	 * @var \Exception
	 */
	protected $e;

	public function connect($config) {
		//Use dsn
		if (!empty($config['dsn'])) {
			$config += Util::parseDsn($config['dsn']);
		}

		try {
			$this->link = new SQLite3($config['file']);
		} catch (\Exception $e) {
			$this->e = $e;
			return false;
		}

		return true;
	}

	public function close() {
		if ($this->link !== null) {
			$this->link->close();
			$this->link = null;
		}
	}

	public function ping() { return true; }

	public function setCharset($charset, $collation='') { return false; }

	public function query($sql) { return $this->link->query($sql); }

	public function insertId() { return $this->link->lastInsertRowID(); }

	public function affectedRows() { return $this->link->changes(); }

	public function begin() { return $this->link->exec('BEGIN TRANSACTION'); }

	public function commit() { return $this->link->exec('COMMIT TRANSACTION'); }

	public function rollback() { return $this->link->exec('ROLLBACK TRANSACTION'); }

	public function getServerVersion() { return $this->getClientVersion(); }

	public function getClientVersion() {
		$v = $this->link->version();
		return $v['versionString'];
	}

	public function errorCode() {
		return $this->link ? $this->link->lastErrorCode() : 0;
	}

	public function errorMessage() {
		return $this->e ? $this->e->getMessage() : $this->link->lastErrorMsg();
	}

}

/**
 * Class ResultSqlite3
 *
 * @package Navigation\Database
 * @property SQLite3Result result
 */
class ResultSqlite3 extends ResultInterface {

	public function fetch($type=DB_ASSOC) {
		return $this->result->fetchArray($type == DB_NUM ? SQLITE3_NUM : SQLITE3_ASSOC);
	}

	public function all($type=DB_ASSOC) {
		$mode = $type == DB_NUM ? SQLITE3_NUM : SQLITE3_ASSOC;
		$data = array();

		$this->result->reset();

		while ($row = $this->result->fetchArray($mode)) {
			$data[] = $row;
		}

		return $data;
	}

	public function rowCount() {
		$count = 0;

		$this->result->reset();

		while ($this->result->fetchArray(SQLITE3_NUM)) {
			++$count;
		}

		return $count;
	}

	public function columnCount() { return $this->result->numColumns(); }

	public function free() {
		$this->result->finalize();
		$this->result = null;
	}

}
