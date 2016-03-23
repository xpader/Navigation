<?php

namespace Navigation\Database;

/**
 * Main Database Class
 *
 * @package Navigation\Database
 */
class Db {

	/**
	 * Database type name
	 *
	 * @var string
	 */
	public $dbType;

	/**
	 * This driver name
	 *
	 * @var string
	 */
	protected $driverName;

	/**
	 * @var DriverInterface
	 */
	protected $driver;

	/**
	 * Result class name
	 *
	 * @var string
	 */
	protected $resultClass;

	/**
	 * Last query SQL string
	 *
	 * @var string
	 */
	public $lastQuery = '';

	/**
	 * Query times total in this DB instance
	 *
	 * @var int
	 */
	public $queryCount = 0;

	/**
	 * All SQL queries in this array if debug is on
	 *
	 * @var array
	 */
	public $queryRecords = array();

	protected $config;
	protected $setNoPrefix = false;

	/**
	 * Adapters List
	 *
	 * @var array
	 */
	protected $allowDrivers = array('mysql', 'mysqli', 'pdo');

	/**
	 * Initialize config
	 *
	 * @param string|array $conf
	 */
	public function __construct($conf='default') {
		if (!is_array($conf)) {
			$NV =& getInstance();
			$config = $NV->config->get($conf, 'database');

			if (!$config) {
				nvCallError("Undefined database config '$conf'");
			}

			$conf = $config;
		}

		$this->config = $conf;

		//Check adapter
		if (!isset($conf['driver'])) {
			$conf['driver'] = 'pdo';
		}

		if (!in_array($conf['driver'], $this->allowDrivers)) {
			nvCallError("Unsupport db adapter '{$conf['driver']}'");
		}

		$this->dbType = strtoupper($conf['type']);

		$this->driverName = $conf['driver'];

		$this->connect($conf);
	}

	/**
	 * Connect to database
	 *
	 * @param $conf
	 */
	public function connect($conf) {
		$adapterClass = '\\Navigation\\Database\\Driver'.ucfirst($this->driverName);
		$this->driver = new $adapterClass();

		if ($this->driver->connect($conf) === false) {
			$message = $this->driver->embedded ? "Failed to open {$conf['type']} database"
				: "Failed to connect to {$conf['type']} server";

			Util::error($message, $this->driver->errorCode(), $this->driver->errorMessage());
		}

		//set result adapter name
		$this->resultClass = '\\Navigation\\Database\\'.ucfirst($this->driverName).'Result';

		//set charset if isset in config
		if (!empty($conf['charset'])) {
			$collate = empty($config['collate']) ? '' : $conf['collate'];
			$this->driver->setCharset($conf['charset'], $collate);
		}
	}

	/**
	 * Execute a query
	 *
	 * @param string $sql
	 * @param bool|true $resultMode
	 * @return ResultInterface|mixed
	 */
	public function query($sql, $resultMode=true) {
		//if debug is open, log queries and time used
		if (!empty($this->config['debug'])) {
			$timeStart = microtime(true);
		}

		$query = $this->driver->query($sql);

		if (!$query) {
			Util::error('Query error', $this->driver->errorCode(), $this->driver->errorMessage(), $sql);
		}

		++$this->queryCount;
		$this->lastQuery = $sql;

		//save debug info
		if (isset($timeStart)) {
			$timeEnd = microtime(true);
			$queryTime = round(($timeEnd - $timeStart), 6);
			$this->queryRecords[] = array('sql' => $sql, 'used' => $queryTime);
		}

		return $resultMode ? new $this->resultClass($query) : $query;
	}

	/**
	 * Get table name with prefix
	 *
	 * @param string $table
	 * @return string
	 */
	public function prefix($table) {
		return $this->config['tbprefix'].$table;
	}

	/**
	 * Escape string for sql query
	 *
	 * @param string $str
	 * @param bool $quote
	 * @return string
	 */
	public function escapeStr($str, $quote=false) {
		$str = addcslashes($str, "\x00\x1a\n\r\\'\"");
		$quote && $str = "'$str'";
		return $str;
	}

	/**
	 * Close database connection
	 *
	 * @return mixed
	 */
	public function close() { $this->driver->close(); }

	/**
	 * Send a ping & Keep alive
	 *
	 * @return mixed
	 */
	public function ping() { return $this->driver->ping(); }

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return int
	 */
	public function insertId() { return $this->driver->insertId(); }

	/**
	 * Get the number of affected rows in previous operation
	 *
	 * @return int
	 */
	public function affectedRows() { return $this->driver->affectedRows(); }

	/**
	 * Begin a transaction
	 *
	 * @return bool
	 */
	public function begin() { return $this->driver->begin(); }

	/**
	 * Commit transaction
	 *
	 * @return bool
	 */
	public function commit() { return $this->driver->commit(); }

	/**
	 * Rollback transaction
	 *
	 * @return bool
	 */
	public function rollback() { return $this->driver->rollback(); }

	/**
	 * Get error code of last error
	 *
	 * @return int
	 */
	public function errorCode() { return $this->driver->errorCode(); }

	/**
	 * Get error message of last error
	 *
	 * @return string
	 */
	public function errorMessage() { return $this->driver->errorMessage(); }

	/**
	 * Get driver connection link resource
	 *
	 * @return mixed
	 */
	public function getConnection() { return $this->driver->link; }

	/**
	 * Get database server version
	 *
	 * @return string
	 */
	public function getServerVersion() { return $this->driver->getServerVersion(); }

	/**
	 * Get this database client version
	 *
	 * @return string
	 */
	public function getClientVersion() { return $this->driver->getClientVersion(); }

}

class Util {

	public static function error($message, $errno, $error, $sql='') {
		nvExit("<h4>Database Error</h4><p><b>Message:</b> {$message} [$errno]<br /><b>Error:</b> $error<br />$sql</p>");
	}

	public static function parseDsn($dsn) {
		$arr = array();

		$split = strpos($dsn, ':');
		$arr['type'] = substr($dsn, 0, $split);

		$pstr = explode(';', substr($dsn, $split+1));

		foreach ($pstr as $row) {
			$split = strpos($row, '=');

			if ($split !== false) {
				$k = substr($row, 0, $split);
				$v = substr($row, $split+1);
			} else {
				$k = 'file';
				$v = $row;
			}

			$arr[trim($k)] = trim($v);
		}

		return $arr;
	}

	public static function buildDsn() {}

}
