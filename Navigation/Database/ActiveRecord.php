<?php

namespace VF\Database;

/**
 * VgotFaster Active Record Class [Abstract]
 *
 * @package VgotFaster
 * @author Pader
 */
abstract class Database_ActiveRecord {

	private $_sqlJoin = array();

	/**
	 * 单表快捷查询
	 *
	 * @param string $table 表名
	 * @param string $cols 字段，多个以逗号连接
	 * @param array $where 查询条件
	 * @param array $cond 其它条件
	 * @return SYS_Database_Result Query Result
	 */
	public function get($table, $cols='*', $where=array(), $cond=array())
	{
		//SELECT
		if($cols != '*') $cols = $this->quoteKeys($cols);

		$SQL = "SELECT $cols FROM ".$this->quoteTable($table);

		//JOIN
		if (count($this->_sqlJoin)) {
			$SQL .= ' '.join(' ',$this->_sqlJoin);
			$this->_sqlJoin = array();
		}

		//WHERE
		if($where) {
			$SQL .=  ' WHERE '.$this->where($where);
		}

		if ($cond) {
			//GROUP BY
			if (isset($cond['groupby'])) {
				$SQL .= ' GROUP BY '.$this->quoteKeys($cond['groupby']);
			}

			if (isset($cond['having'])) {
				$SQL .= ' HAVING '.$this->where($cond['having']);
			}

			//ORDER BY
			if (isset($cond['orderby'])) {
				$SQL .= ' ORDER BY '.preg_replace_callback('/([^\s,].+?)\s+?(ASC|DESC)/i', array($this, 'callbackReplaceOrderBy'), $cond['orderby']);
			}

			//LIMIT
			if(isset($cond['limit'])) {
				$SQL .= ' LIMIT '.$cond['limit'];
				if (isset($cond['offset'])) {
					$SQL .= ' OFFSET '.$cond['offset'];
				}
			}
		}

		return $this->query($SQL);
	}

	//For preg_replace_callback of ORDER BY query
	private function callbackReplaceOrderBy($m) {
		return $this->quoteKeys($m[1]).' '.strtoupper($m[2]);
	}

	/**
	 * 设置 JOIN 查询
	 *
	 * @param string $table
	 * @param string|array $compopr
	 * @param string $type LEFT|INNER|OUTER RIGHT..
	 * @param string $compoprType AND|OR
	 * @return object
	 */
	public function join($table, $compopr, $type='', $compoprType='AND')
	{
		$SQL = $type ? strtoupper($type).' ' : '';
		$SQL .= 'JOIN '.$this->quoteTable($table);

		if ($type != '') {
			if (!is_array($compopr)) {
				if (preg_match('/^[\w_]+$/',$compopr)) {
					$SQL .= " USING(`$compopr`)";
					$this->_sqlJoin[] = $SQL;
					return $this;
				}

				$compopr = array($compopr);
			}

			$compoprArray = array();

			foreach ($compopr as $row) {
				$row = trim($row);
				$compoprArray[] = preg_match('/^([\w\.]+)?\s*(.+?)\s*([\w\.]+)$/', $row, $match)
					? '`'.str_replace('.','`.`',$match[1]).'`'.$match[2].'`'.str_replace('.','`.`',$match[3]).'`'
					: $compopr;
			}

			$SQL .= ' ON '.join(' '.$compoprType.' ', $compoprArray);
		}

		$this->_sqlJoin[] = $SQL;

		return $this;
	}

	/**
	 * 执行新增操作，并返回影响记录条数
	 *
	 * @param string $table 表名
	 * @param array $data 键名对应数据
	 * @param bool $replace 是否以 REPLACE 方式插入
	 * @return SYS_Database_Result
	 */
	public function insert($table,array $data,$replace=FALSE)
	{
		if(!is_array($data) or count($data) === 0) {
			showError('<b>DB::insert()</b> Argument 2 must be an array!');
		}

		$table = $this->quoteTable($table);
		$insert = $this->quoteData($data);

		$SQL = ($replace ? 'REPLACE' : 'INSERT')." INTO $table({$insert[0]}) VALUES({$insert[1]})";

		return $this->exec($SQL);
	}

	/**
	 * 执行更新操作，并返回影响记录条数
	 *
	 * @param string $table 表名
	 * @param array $data 表名前带有 ^ 符号，则方法将不会用引号包括值
	 * @param array $where Where
	 * @param string $whereType AND|OR
	 * @return int
	 */
	public function update($table, array $data, $where=array(), $whereType='AND')
	{
		$SQL = 'UPDATE '.$this->quoteTable($table).' SET ';

		if(!is_array($data) or count($data) === 0) {
			showError('<b>DB::update()</b> Argument 2 must be an array!');
		}

		$sets = array();
		foreach($data as $key => $val) {
			if(substr($key,0,1) == '^') {
				$key = $this->quoteKeys(substr($key,1));
				$sets[] = "$key=$val";
			} else {
				$sets[] = $this->quoteKeys($key).'='.$this->escapeStr($val, true);
			}
		}

		$SQL .= join(',',$sets);

		if($where) {
			$SQL .= ' WHERE '.$this->where($where,NULL,$whereType);
		}

		return $this->exec($SQL);
	}

	/**
	 * 执行删除操作，并返回影响记录条数
	 *
	 * @param string $table 表名
	 * @param array $where WHERE
	 * @param string $whereType AND|OR
	 * @return int
	 */
	public function delete($table,$where=array(),$whereType='AND')
	{
		$SQL = 'DELETE FROM '.$this->quoteTable($table);

		if($where) {
			$SQL .= ' WHERE '.$this->where($where,NULL,$whereType);
		}

		return $this->exec($SQL);
	}

	/**
	 * 生成 WHERE 条件语句
	 *
	 * Example: where(array('id'=>2,'tb.name !='=>'kick'))
	 *
	 * @param array|string where 键名或一组条件数组
	 * @param string 第一个参数为键名时的值
	 * @param string $type AND|OR
	 * @return string
	 */
	public function where($where, $value=NULL, $type='AND')
	{
		//Todo: 当出现 array('key'=>null) 时拼出的 SQL 会忽略该字段值的查询，造成可能的重大安全隐患，所以目前不可在数组中字段值使用 null 值
		if (is_array($where)) {
			$sqlWhere = array();
			foreach ($where as $key => $val) {
				$sqlWhere[] = is_int($key) ? $val : $this->where($key, $val);
			}
			return join(" $type ",$sqlWhere);
		} else {
			if ($value === null) {
				return $where;
			}

			if ($pos = strpos($where, ' ')) {
				$key = $this->quoteKeys(substr($where, 0, $pos));
				$cond = strtoupper(trim(substr($where, $pos+1)));

				switch ($cond) {
					case ':':
					case '!:':
						$cond = str_replace(array('!:', ':'), array('NOT IN', 'IN'), $cond);
					case 'IN':
					case 'NOT IN':
						return $key.' '.$cond.'('.$this->quoteValues($value).')';
						break;

					case '%':
					case '!%':
						$cond = str_replace(array('!%', '%'), array('NOT LIKE', 'LIKE'), $cond);
					case 'LIKE':
					case 'NOT LIKE':
						return $key.' '.$cond.' '.$this->quoteValues($value);
						break;

					default:
						return $key.$cond.$this->quoteValues($value);
				}

			} else {
				return $this->quoteKeys($where).'='.$this->quoteValues($value);
			}
		}
	}

	/**
	 * Quote Table
	 *
	 * @param string $table
	 * @return string
	 */
	public function quoteTable($table)
	{
		strpos($table,'.') && $table = str_replace('.','`.`',$table);
		strpos($table,' ') && $table = str_replace(' ', '` `', $table);

		if(substr($table,0,1) == '^') {
			$table = substr($table,1);
		} elseif($this->hasPrefix($table) == FALSE) {
			$table = $this->prefix($table);
		}

		return '`'.$table.'`';
	}

	/**
	 * 根据参数数组中的键名和值返回一个索引0,1和键值为keys,values的字段与内容数组
	 *
	 * @param array $data
	 * @return array
	 */
	public function quoteData($data)
	{
		$keys = array_keys($data);
		$return = array($this->quoteKeys($keys),$this->quoteValues($data));
		list($return['keys'],$return['values']) = $return;
		return $return;
	}

	/**
	 * Convert Keys To SQL Format
	 *
	 * @param string|array $keys
	 * @return string
	 */
	public function quoteKeys($keys)
	{
		if(is_array($keys)) {
			$Qkeys = array();
			foreach($keys as $key) {
				$Qkeys[] = $this->quoteKeys($key);
			}
			return join(',',$Qkeys);
		} elseif(strpos($keys,',') !== FALSE) {
			$keys = explode(',',$keys);
			return $this->quoteKeys($keys);
		} else {
			$keys = $col = trim($keys);
			$str = $pre = $func = $as = '';

			//as alias
			if (stripos($keys,' AS ') !== FALSE) {
				list($col, $as) = explode(' AS ', str_replace(' as ', ' AS ', $keys));
				$as = trim($as, ' `');
			}

			//used function
			if (preg_match('/^(\w+)\s*\(\s*(.+)\s*\)/i', $col, $m)) {
				$func = $m[1];
				$col = $m[2];
			}

			//has prefix
			if (strpos($col,'.') !== FALSE) {
				list($pre, $col) = explode('.', $col);
				$pre = trim($pre, ' `');
			}

			//make return string
			$pre && $str = "`$pre`.";
			($col != '*' && !ctype_digit($col)) && $col = "`$col`";
			$func ? $str = "$func($str$col)" : $str .= $col;
			$as && $str .= " AS `$as`";

			return $str;
		}
	}


	/**
	 * 返回被转义并使用引号包括了字符串的值，数组形式的多个将由逗号连接
	 *
	 * @param mixed $values
	 * @return string Keys
	 */
	public function quoteValues($values)
	{
		if(is_array($values)) {
			$vals = array();
			foreach($values as $val) {
				$vals[] = $this->quoteValues($val);
			}
			return join(',', $vals);
		} else {
			//全部使用引号更加规范，并且没有任何明显的性能损失影响
			//不加引号则对于字符串类型字段使用如 col=0 或 col=123 会有明显的错误问题和性能影响
			//return preg_match('/^\d+(\.\d+)?$/',$values) ? $values : $this->escapeStr($values, true);
			return $this->escapeStr($values, true);
		}
	}

}
