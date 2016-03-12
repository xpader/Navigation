<?php
/**
 * VgotFaster PHP Framework
 *
 * @package VgotFaster
 * @author pader
 * @copyright Copyright (c) 2009-2014, VGOT.NET
 * @link http://www.vgot.net/ http://vgotfaster.googlecode.com
 * @filesource
 */

namespace VF\Database;

require_once __DIR__.'/Database_ActiveRecord.php';

use \PDO;
use \PDOException;
use \PDOStatement;

/**
 * VgotFaster Database Object
 *
 * 采用 PDO 驱动
 *
 * @package VgotFaster
 * @author Pader
 */
class Database extends Database_ActiveRecord {

	public $queryCount = 0;
	public $queryRecords = array();
	public $lastQuery = '';

	private $config;
	private $setNoPrefix = FALSE;

	/**
	 * @var PDO
	 */
	protected $pdo = NULL;

	/**
	 * @var PDOStatement
	 */
	protected $sth;

	/**
	 * 连接数据库
	 *
	 * @param  $config 连接的配置数组
	 * @return void
	 */
	public function connect($config)
	{
		$args = array();

		//firebird,mssql,mysql,oci,oci8,odbc,pgsql,sqlite
		switch ($config['dbdriver']) {
			case 'mysql':
				$dsnp = $opt = array();

				$dsnp['host'] = $config['hostname'];
				$dsnp['dbname'] = $config['database'];

				if (isset($config['port'])) $dsnp['port'] = $config['port'];

				if ($config['pconnect']) $opt[PDO::ATTR_PERSISTENT] = TRUE;

				if (!empty($config['charset'])) {
					$dsnp['charset'] = $config['charset'];
					if ($config['dbdriver'] == 'mysql') {
						$set = "SET NAMES '{$config['charset']}'";
						empty($config['dbcollat']) || $set .= " COLLATE '{$config['dbcollat']}'";
						$opt[PDO::MYSQL_ATTR_INIT_COMMAND] = $set;
					}
				}

				$dsn = array();

				foreach ($dsnp as $k => $v) {
					$dsn[] = $k.'='.$v;
				}

				$dsn = $config['dbdriver'].':'.join(';',$dsn);
				$args = array($dsn, $config['username'], $config['password']);

				$opt && $args[3] = $opt;
				break;

			case 'sqlite':
				$dsn = "sqlite:{$config['filename']}";
				$args = array($dsn);
				break;
			default:
				$this->error('Unable to connect database', "Not yet supported database driver: '{$config['dbdriver']}'");
		}

		//Connect
		try {
			$class= new \ReflectionClass('PDO');
			$this->pdo = $class->newInstanceArgs($args);
		} catch (PDOException $e) {
			$this->error('Failed to connect database', $e);
		}

		$this->config = $config;
	}

	/**
	 * 检查 MySQL 是否成功连接
	 *
	 * @return bool
	 */
	public function ping()
	{
		//PDO 无法实现 ping 功能，此方法仅是兼容性的代替方法！
		return $this->pdo ? true : false;
	}

	/**
	 * 激活并连接指定配置
	 *
	 * @param string $configName 配置名称
	 * @return void
	 */
	public function exchange($configName)
	{
		$VF =& getInstance();

		$this->close();
		$config = $VF->config->get('database',$configName);
		$this->connect($config);
	}

	/**
	 * 执行一条 SQL 语句，并返回相关资源
	 *
	 * @param string $sql
	 * @param bool $resultMode
	 * @return Result Object | PDOStatement
	 */
	public function query($sql, $resultMode=TRUE)
	{
		$this->sth = $this->exec($sql,TRUE);
		return $resultMode ? new SYS_Database_Result($this->sth) : $this->sth;
	}

	/**
	 * 执行一条 SQL 语句
	 *
	 * @param string $sql
	 * @param bool $PDOStatement 返回 PDOStatement 对象或是受影响的记录条数
	 * @return int
	 */
	public function exec($sql, $PDOStatement=FALSE)
	{
		if (!empty($this->config['debug'])) $qst = microtime(true);

		$rt = $PDOStatement ? $this->pdo->query($sql) : $this->pdo->exec($sql);

		if ($this->pdo->errorCode() == '00000') {
			$this->queryCount++;
			$this->lastQuery = $sql;
		} else {
			$this->error('Query Error', $sql);
		}

		if (isset($qst)) {
			$qse = microtime(true);
			$qst = round(($qse - $qst), 6);
			$this->queryRecords[] = array('sql'=>$sql,'used'=>$qst);
		}

		return $rt;
	}

	/**
	 * 遍历查询结果集中的数据
	 *
	 * @param PDOStatement $sth
	 * @param int $style
	 * @return array
	 */
	public function fetch($sth=NULL,$style=PDO::FETCH_ASSOC)
	{
		if (!is_null($sth)) {
			$sth instanceof PDOStatement || showError('Result is not a PDOStatement in DB fetch');
		} elseif ($this->sth instanceof PDOStatement) {
			$sth =& $this->sth;
		} else {
			showError('Resource doesn\'t exists in DB fetch');
		}

		return $sth->fetch($style);
	}

	/**
	 * 判断表名是否有配置中的前缀
	 *
	 * @param string $tableName
	 * @return bool
	 */
	public function hasPrefix($tableName)
	{
		if (empty($this->config['tbprefix']) || $this->setNoPrefix || substr($tableName, 0, strlen($this->config['tbprefix'])) == $this->config['tbprefix']) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 设置不添加表前缀
	 *
	 * 设置后， hasPrefix 将始终返回 TRUE，ActiveRecord 也将始终不添加前缀
	 *
	 * @param bool $set
	 * @return void
	 */
	public function setNoPrefix($set=TRUE) { $this->setNoPrefix = $set; }

	/**
	 * 取得带配置前缀的表名
	 *
	 * @param string $table
	 * @return string
	 */
	public function prefix($table) { return $this->config['tbprefix'].$table; }

	/**
	 * 取得上一步操作产生的 ID
	 *
	 * @param string $name
	 * @return int
	 */
	public function lastId($name=NULL) { return $this->pdo->lastInsertId($name); }

	/**
	 * 转义字符串用于查询
	 *
	 * @param string $str
	 * @param bool $quote
	 * @return string
	 */
	public function escapeStr($str, $quote=false) { return $quote ? $this->pdo->quote($str) : substr($this->pdo->quote($str), 1, -1); }

	/**
	 * 取得前一次 SQL 操作所影响的记录行数
	 *
	 * 增删改影响及查询结果记录数
	 *
	 * @return int
	 */
	public function affectedRows() { return $this->sth->rowCount(); }

	/**
	 * 开始一个事务
	 *
	 * @return bool
	 */
	public function begin() { return $this->pdo->beginTransaction(); }

	/**
	 * 提交事务
	 *
	 * @return bool
	 */
	public function commit() { return $this->pdo->commit(); }

	/**
	 * 回滚事务
	 *
	 * @return bool
	 */
	public function rollback() { return $this->pdo->rollBack(); }

	/**
	 * 取得数据库连接对象或标识
	 *
	 * @return PDO
	 */
	public function getConnection() { return $this->pdo; }
	
	//获取 MySQL 服务端版本信息
	public function getServerVersion() { return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION); }

	//获取 MySQL 客户端版本信息
	public function getClientVersion() { return $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION); }

	/**
	 * 错误信息输出
	 *
	 * @param string $errorName
	 * @param object|string $err
	 * @return void
	 */
	private function error($errorName, $err='')
	{
		if (is_object($err)) {
			$msg = $err->getMessage();
			$err = '';
		} elseif ($this->pdo && $this->pdo->errorCode() != '00000') {
			$errInfo = $this->pdo->errorInfo();
			$msg = $errInfo[2];
		} else {
			$msg = $err;
			$err = null;
		}

		exit('<div style="border:1px solid #999;padding:20px;margin:5px;font-size:14px;line-height:25px;font-family:Verdana,Arial,宋体;color:#000;">'.
			'<span style="display:block;padding-bottom:10px;color:#900;font-size:16px;">Database Error</span>'.
			'<span style="color:#777;">Error: </span>'.$errorName.'<br /><span style="color:#777;">Message: </span>'.$msg.'<br />'.
			($err ? '<span style="color:#777;">Query: </span><span>'.htmlspecialchars($err).'</span>' : '').
			'</div>');
	}

	/**
	 * 关闭数据库连接
	 *
	 * @return void
	 */
	public function close()
	{
		if (!is_null($this->pdo)) {
			$this->pdo = NULL;
		}
	}

	public function __destruct()
	{
		$this->close();
	}

}

/**
 * VgotFaster DB Result Class
 *
 * @package VgotFaster
 * @author Pader
 */
class SYS_Database_Result {

	/**
	 * @var PDOStatement
	 */
	public $sth;

	public function __construct($sth)
	{
		$this->sth = $sth;
	}

	/**
	 * 返回查询资源结果数组
	 *
	 * @param string $idKey 使用 id 或某一字段的值作为返回结果集数组索引名称
	 * @return array
	 */
	public function result($idKey='')
	{
		$rows = array();

		if (empty($idKey)) {
			while($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {
				$rows[] = $row;
			}
		} else {
			//按照字段名给第一维键名命名,不建议使用唯一、自动增加以外类型的字段
			while($row = $this->sth->fetch(PDO::FETCH_ASSOC)) {
				$rows[$row[$idKey]] = $row;
			}
		}

		return $rows;
	}

	/**
	 * 从查询资源中索引出一行数据
	 *
	 * @param string $field 只返回字段中的一个值, 使用整型数字代表第几个字段，字符串则为字段名称，为空则返回整个字段数组
	 * @return array|string
	 */
	public function row($field=NULL)
	{
		$fetchStyle = is_int($field) ? PDO::FETCH_NUM : PDO::FETCH_ASSOC;
		$row = $this->sth->fetch($fetchStyle);

		if (is_array($row) && count($row) > 0) {
			 return is_null($field) ? $row : (isset($row[$field]) ? $row[$field] : NULL);
		} else {
			return NULL;
		}
	}

	/**
	 * 获取查询的原始资源
	 *
	 * @return PDOStatement
	 */
	public function stmt() {
		return $this->sth;
	}

	/**
	 * 取得结果集中行的数目
	 *
	 * @return int
	 */
	public function rowCount() { return $this->sth->rowCount(); }

	/**
	 * 取得结果集中列的数目
	 *
	 * @return int
	 */
	public function columnCount() { return $this->sth->columnCount(); }

}
