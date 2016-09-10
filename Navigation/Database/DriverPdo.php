<?php

namespace Navigation\Database;

use \PDO;
use \PDOStatement;
use \PDOException;

/**
 * PDO driver of database
 *
 * This is a driver adapter for database class
 *
 * @package Navigation\Database
 * @property PDO link
 */
class DriverPdo extends DriverInterface {

	/**
	 * @var PDOException
	 */
	protected $e;

	/**
	 * @var PDOStatement
	 */
	protected $sth;

	public function connect($config) {
		//firebird,mssql,mysql,oci,oci8,odbc,pgsql,sqlite
		switch ($config['type']) {
			case 'sqlite':
				$dsn = "sqlite:{$config['file']}";
				$args = array($dsn, null, null, array(PDO::ATTR_TIMEOUT=>5));
				$this->embedded = true;
				break;

			default:
				if (empty($config['dsn'])) {
					$dsn = $config['type'].":host={$config['host']};dbname={$config['dbname']}";

					if (!empty($config['port'])) {
						$dsn .= ";port={$config['port']}";
					}

				} else {
					$dsn = $config['dsn'];
				}

				$args = array($dsn, $config['username'], $config['password']);

				//PDO options
				$options = array();

				if ($config['pconnect']) $options[PDO::ATTR_PERSISTENT] = true;

				$options && $args[3] = $options;
				break;
		}

		//Connect
		try {
			$class = new \ReflectionClass('PDO');
			$this->link = $class->newInstanceArgs($args);
		} catch (PDOException $e) {
			$this->e = $e;
			return false;
		}

		return true;
	}

	public function close() {
		if ($this->link !== null) {
			$this->link = null;
			$this->sth = null;
		}
	}

	public function ping() {
		return false;
	}

	public function setCharset($charset, $collation='') {
		$set = "SET NAMES '$charset'";

		if ($collation) {
			$set .= " COLLATE '$collation'";
		}

		return $this->link->exec($set);
	}

	public function query($sql) {
		return $this->sth = $this->link->query($sql);
	}

	public function insertId() {
		return $this->link->lastInsertId();
	}

	public function affectedRows() {
		return $this->sth->rowCount();
	}

	public function begin() { return $this->link->beginTransaction(); }

	public function commit() { return $this->link->commit(); }

	public function rollback() { return $this->link->rollBack(); }

	public function getServerVersion() { return $this->link->getAttribute(PDO::ATTR_SERVER_VERSION); }

	public function getClientVersion() { return $this->link->getAttribute(PDO::ATTR_CLIENT_VERSION); }

	public function errorCode() {
		return $this->link ? $this->link->errorCode() : 0;
	}

	public function errorMessage() {
		if ($this->e) {
			return $this->e->getMessage();
		} elseif ($this->link) {
			$errorInfo = $this->link->errorInfo();
			return $errorInfo[2];
		}

		return '';
	}

}

class ResultPdo extends ResultInterface {

	public function fetch($type=DB_ASSOC) {
		return $this->result->fetch($type == DB_NUM ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
	}

	public function all($type=DB_ASSOC) {
		return $this->result->fetchAll($type == DB_NUM ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
	}

	public function column($columnNumber=0) {
		return $this->result->fetchColumn($columnNumber);
	}

	public function rowCount() { return $this->result->rowCount(); }

	public function columnCount() { return $this->result->columnCount(); }

	public function free() { $this->result = null;}

}
