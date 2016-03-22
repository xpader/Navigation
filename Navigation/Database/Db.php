<?php

namespace Navigation\Database;

/**
 * Main Database Class
 *
 * @package Navigation\Database
 */
abstract class Db {

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
	 * This driver name
	 *
	 * @var string
	 */
	protected $driverName;

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
	protected $allowAdapters = array('mysql', 'mysqli', 'pdo');

	/**
	 * Driver connection
	 */
	public $link;

	protected $query;

	/**
	 * Initialize config
	 *
	 * @param string|array $conf
	 * @param bool $useDsn Unsupport yet
	 */
	public function __construct($conf='default', $useDsn=false) {
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

		if (!in_array($conf['driver'], $this->allowAdapters)) {
			nvCallError("Unsupport db adapter '{$conf['driver']}'");
		}

		$this->dbType = strtoupper($conf['type']);

		//$this->driverName = $conf['adapter'];

		$this->connect($conf);
	}

	/**
	 * Connect to database
	 *
	 * @param $conf
	 */
	public function connect($conf) {
		if ($this->realConnect($conf) === false) {
			$message = $this->embedded ? "Failed to open {$this->dbType} database"
				: "Failed to connect to {$this->dbType} server";

			Util::error($message, $this->errorCode(), $this->errorMessage());
		}

		//set result adapter name
		$this->resultClass = '\\Navigation\\Database\\'.ucfirst($this->driverName).'Result';
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

		$this->query = $this->execute($sql);

		if (!$this->query) {
			Util::error('Query error', $this->errorCode(), $this->errorMessage(), $sql);
		}

		++$this->queryCount;
		$this->lastQuery = $sql;

		//save debug info
		if (isset($timeStart)) {
			$timeEnd = microtime(true);
			$queryTime = round(($timeEnd - $timeStart), 6);
			$this->queryRecords[] = array('sql' => $sql, 'used' => $queryTime);
		}

		return $resultMode ? new $this->resultClass($this->query) : $this->query;
	}

	/**
	 * 转义字符串用于查询
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
	 * Connect to database in driver
	 *
	 * @param array $config
	 * @return mixed
	 */
	abstract protected function realConnect($config);

	/**
	 * Close database connection
	 *
	 * @return mixed
	 */
	abstract public function close();

	/**
	 * Send a ping & Keep alive
	 *
	 * @return mixed
	 */
	abstract public function ping();

	/**
	 * Execute a sql query
	 *
	 * @param string $sql
	 * @return mixed
	 */
	abstract public function execute($sql);

	/**
	 * Get table name with prefix
	 *
	 * @param string $table
	 * @return string
	 */
	public function prefix($table) { return $this->config['tbprefix'].$table; }

	/**
	 * 取得上一步操作产生的 ID
	 *
	 * @return int
	 */
	abstract public function lastId();

	/**
	 * 取得前一次 MySQL 操作所影响的记录行数
	 *
	 * @return int
	 */
	abstract public function affectedRows();

	/**
	 * 开始一个事务
	 *
	 * @return bool
	 */
	abstract public function begin();

	/**
	 * 提交事务
	 *
	 * @return bool
	 */
	abstract public function commit();

	/**
	 * 回滚事务
	 *
	 * @return bool
	 */
	abstract public function rollback();

	abstract public function errorCode();

	abstract public function errorMessage();

	/**
	 * 取得数据库连接对象或标识
	 *
	 * @return mixed
	 */
	public function getConnection() { return $this->link; }

	//获取 MySQL 服务端版本信息
	abstract public function getServerVersion();

	//获取 MySQL 客户端版本信息
	abstract public function getClientVersion();

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

class Util {

	public static function error($message, $errno, $error, $sql='') {
		nvExit("<h4>Database Error</h4><p><b>Message:</b> {$message} [$errno]<br /><b>Error:</b> $error<br />$sql</p>");
	}

	public static function parseDsn() {}

	public static function buildDsn() {}

}
