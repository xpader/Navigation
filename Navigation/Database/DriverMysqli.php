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

use \mysqli;
use \mysqli_result;

/**
 * VgotFaster MySQL Operation Class
 *
 * @package VgotFaster
 * @author Pader
 */
class Mysql extends Database_ActiveRecord {

	public $queryCount = 0;
	public $queryRecords = array();
	public $lastQuery = '';

	private $config;
	private $setNoPrefix = FALSE;

	/**
	 * @var mysqli
	 */
	private $link = NULL;
	private $query = NULL;

	public function __destruct()
	{
		$this->close();
	}

	public function close()
	{
		if (!is_null($this->link)) {
			@mysqli_close($this->link);
			$this->link = NULL;
		}
	}

	/**
	 * 连接数据库
	 *
	 * @param  $config 连接的配置数组
	 * @return void
	 */
	public function connect($config)
	{
		$port = null;
		$dbname = isset($config['database']) ? $config['database'] : null;

		if (strpos($config['hostname'], ':') !== false) {
			list($host, $port) = explode(':', $config['hostname']);
		} else {
			$host = $config['hostname'];
			if (isset($config['port'])) {
				$port = $config['port'];
			}
		}

		$config['pconnect'] && $host = 'p:'.$host;

		$this->link = @mysqli_connect($host, $config['username'], $config['password'], $dbname, $port);

		if (!$this->link) {
			$this->error('Failed to connect to MySQL server', '', '['.mysqli_connect_errno().'] '.mysqli_connect_error());
		}

		//Test Connect
		//$this->ping() || $this->error('Lost connection to MySQL server');

		//Set Chars
		if (isset($config['charset']) && $config['charset'] != '') $this->setChars($config['charset'],$config['dbcollat']);

		$this->config = $config;
	}

	/**
	 * 检查 MySQL 是否成功连接
	 *
	 * @return bool
	 */
	public function ping()
	{
		return ($this->link instanceof mysqli) && mysqli_ping($this->link);
	}

	public function dbSelect($database)
	{
		return @mysqli_select_db($this->link, $database) || $this->error('Select the database failed');
	}

	/**
	 * 激活并连接指定配置
	 *
	 * @param string $configName 配置名称
	 * @return void
	 */
	public function exchange($configName)
	{
		$this->close();
		$config = getInstance()->config->get('database',$configName);
		$this->connect($config);
	}

	/**
	 * 字符集设置
	 *
	 * @param string $charset
	 * @param string $collation
	 * @return Query
	 */
	public function setChars($charset, $collation='')
	{
		$set = "SET NAMES '$charset'";

		if ($collation != '') {
			$set .= " COLLATE '$collation'";
		}

		return @mysqli_query($this->link, $set) || $this->error('Set charset error', $set);
	}

	/**
	 * 执行一条 SQL 语句，并返回相关资源
	 *
	 * @param string $SQL
	 * @param bool $resultMode
	 * @return SYS_MySQL_Result|mysqli_result Query Result
	 */
	public function query($SQL, $resultMode=true)
	{
		//if debug, start
		if (isset($this->config['debug']) && $this->config['debug']) {
			$mtime = explode(' ', microtime());
			$queryStartTime = $mtime[1] + $mtime[0];
		}

		$this->query = @mysqli_query($this->link, $SQL) or $this->error('Query error',$SQL);
		$this->queryCount++;
		$this->lastQuery = $SQL;

		//if debug, end
		if(isset($queryStartTime)) {
			$mtime = explode(' ', microtime());
			$queryEndtTime = $mtime[1] + $mtime[0];
			$queryTime = round(($queryEndtTime - $queryStartTime), 6);
			$this->queryRecords[] = array('sql'=>$SQL,'used'=>$queryTime);
		}

		return $resultMode ? new SYS_MySQL_Result($this->query) : $this->query;
	}

	/**
	 * 执行一条 SQL 语句
	 *
	 * @param string $sql
	 * @return Query #Res
	 */
	public function exec($sql) {
		return $this->query($sql, false);
	}

	/**
	 * Fetch Row From DB Result
	 *
	 * @param resource $result
	 * @param int $type
	 * @return array
	 */
	public function fetch($result=null, $type=MYSQLI_ASSOC)
	{
		if ($result !== null) {
			($result instanceof mysqli_result) || showError('Result is not a resource type in DB fetch');
		} elseif ($this->query instanceof mysqli_result) {
			$result =& $this->query;
		} else {
			showError('Resource doesn\'t exists in DB fetch');
		}

		return mysqli_fetch_array($result, $type);
	}

	/**
	 * 判断表名是否有配置中的前缀
	 *
	 * @param string $tableName
	 * @return bool
	 */
	public function hasPrefix($tableName)
	{
		return (empty($this->config['tbprefix']) || $this->setNoPrefix || substr($tableName, 0 ,strlen($this->config['tbprefix'])) == $this->config['tbprefix']);
	}

	//设置不添加表前缀，设置后， hasPrefix 将始终返回 TRUE，ActiveRecord 也将始终不添加前缀
	public function setNoPrefix($set=TRUE) { $this->setNoPrefix = $set; }

	//取得带配置前缀的表名
	public function prefix($table) { return $this->config['tbprefix'].$table; }

	//取得上一步操作产生的 ID
	public function lastId() { return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->query('SELECT LAST_INSERT_ID()')->row(0); }

	/**
	 * 转义字符串用于查询
	 *
	 * @param string $str
	 * @param bool $quote
	 * @return string
	 */
	public function escapeStr($str, $quote=false) {
		$str = mysqli_real_escape_string($this->link, $str); 
		if ($quote) {
			$str = "'$str'";
		}
		return $str;
	}

	//取得前一次 MySQL 操作所影响的记录行数
	public function affectedRows() { return mysqli_affected_rows($this->link); }

	/**
	 * 开始一个事务
	 *
	 * @return bool
	 */
	public function begin() { return PHP_VERSION_ID >= 50500 ? mysqli_begin_transaction($this->link) : $this->exec('BEGIN'); }

	/**
	 * 提交事务
	 *
	 * @return bool
	 */
	public function commit() { return mysqli_commit($this->link); }

	/**
	 * 回滚事务
	 *
	 * @return bool
	 */
	public function rollback() { return mysqli_rollback($this->link); }

	/**
	 * 取得数据库连接对象或标识
	 *
	 * @return mysqli
	 */
	public function getConnection() { return $this->link; }

	//获取 MySQL 服务端版本信息
	public function getServerVersion() { return mysqli_get_server_info($this->link); }

	//获取 MySQL 客户端版本信息
	public function getClientVersion() { return mysqli_get_client_info($this->link); }

	/**
	 * 错误信息输出
	 *
	 * @param string $title
	 * @param string $SQL
	 * @param string $realError
	 * @return void
	 */
	private function error($title, $SQL='', $realError='')
	{
		$err = $realError == '' ? mysqli_error($this->link) : $realError;
		exit('<div style="border:1px solid #999;padding:20px;margin:5px;font-size:13px;line-height:25px;font-family:Verdana,Arial,宋体;color:#000;">'.
			'<span style="display:block;padding-bottom:10px;color:#900;font-size:15px;">MySQL Error</span>'.
			'<span style="color:#777;">Error: </span>'.$title.'<br /><span style="color:#777;">Message: </span>'.$err.'<br />'.
			(!empty($SQL) ? '<span style="color:#777;">Query: </span><span>'.htmlspecialchars($SQL).'</span>' : '').
			'</div>');
	}

}

/**
 * VgotFaster DB Result Class
 *
 * @package VgotFaster
 * @author Pader
 */
class SYS_MySQL_Result {

	public $result;

	public function __construct($result)
	{
		$this->result = $result;
	}

	/**
	 * 返回查询资源结果数组
	 *
	 * @param string $idKey 用于作为数组索引的[唯一值]字段
	 * @return array
	 */
	public function result($idKey='')
	{
		$rows = array();

		if ($idKey == '') {
			while ($row = mysqli_fetch_assoc($this->result)) {
				$rows[] = $row;
			}
		} else {
			//按照字段名给第一维键名命名,不建议使用唯一、自动增加以外类型的字段
			while ($row = mysqli_fetch_assoc($this->result)) {
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
		$resultType = is_int($field) ? MYSQLI_NUM : MYSQLI_ASSOC;
		$row = mysqli_fetch_array($this->result, $resultType);

		if (is_array($row) && count($row) > 0) {
			 return is_null($field) ? $row : (isset($row[$field]) ? $row[$field] : NULL);
		} else {
			return NULL;
		}
	}

	/**
	 * 获取查询的原始资源
	 *
	 * @return mysqli_result
	 */
	public function stmt() {
		return $this->result;
	}

	//取得行的数目
	public function rowCount() { return mysqli_num_rows($this->result); }

	//取得结果集中字段的数目
	public function columnCount() { return mysqli_num_fields($this->result); }

}
