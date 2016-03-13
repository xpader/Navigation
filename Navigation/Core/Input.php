<?php

namespace Navigation\Core;

class Input {

	/**
	 * @var array
	 */
	protected $uri;
	protected $connection;

	public function __construct(&$uri, $connection) {
		$this->uri =& $uri;
		$this->connection = $connection;
	}

	public function get($index) {
		return $this->fetchArray($_GET, $index);
	}

	public function post($index) {
		return $this->fetchArray($_POST, $index);
	}

	public function cookie($index) {
		return $this->fetchArray($_COOKIE, $index);
	}

	public function server($index) {
		return $this->fetchArray($_SERVER, $index);
	}

	protected function fetchArray(&$array, $index) {
		if (is_array($index)) {
			$data = array();

			foreach ($index as $key) {
				$data[$key] = $this->fetchArray($array, $key);
			}

			return $data;
		}

		//Todo: do something filter(security) for the value before return
		return isset($array[$index]) ? $array[$index] : null;
	}


	/**
	 * Get URI segment
	 *
	 * @param int $number
	 * @return string
	 */
	public function segment($number) {
		--$number;
		return isset($this->uri['array'][$number]) ? $this->uri['array'][$number] : null;
	}

	/**
	 * Get the params list in uri
	 *
	 * 可以用于 list() 把参数具体变量化
	 * 例：list($id,$page,$style) = $this->input->params(3);
	 * 使用 function action($id='',$page='',$style='') 的缺点是你必须设定每个参数的默认值，比较繁琐
	 * 如果没有设定默认值，则 PHP 会在参数不完整时报错
	 *
	 * @param int|bool $length 返回参数数组的长度，当参数数组长度不够时，会自动使用 NULL 填充到此长度以确保 list() 能正常工作
	 * @return array Params
	 */
	public function params($length=true) {
		if ($length === true || isset($this->uri['params'][$length-1])) {
			return $this->uri['params'];
		} else {
			return array_pad($this->uri['params'], $length, null);
		}
	}

	/**
	 * Return segment name/value/name/value like an array
	 *
	 * @param bool $pos Which assoc start forom segment, default is after action name
	 * @return array
	 */
	public function assoc($pos=true) {
		if ($pos === true) {
			$params = $this->uri['params'];
		} else {
			$params = $pos > 1 ? array_slice($this->uri['array'], $pos-1) : $this->uri['array'];
		}

		$assoc = array();
		foreach (array_chunk($params,2) as $row) {
			$assoc[$row[0]] = isset($row[1]) ? $row[1] : NULL;
		}

		return $assoc;
	}

	/**
	 * Get URI Parameter
	 *
	 * file|controller|action|params|string|uri|route
	 *
	 * @param string $key
	 * @return string|array
	 */
	public function uri($key=null) {
		if ($key === null) {
			return $this->uri;
		} else {
			return $this->uri[$key];
		}
	}

	/**
	 * Get client Ip Address
	 *
	 * @return string
	 */
	public function ip()
	{
		$ip = '';

		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $k) {
			if (!empty($_SERVER[$k])) {
				$ip = $_SERVER[$k];
				break;
			}
		}

		if ($ip && preg_match('/[\d\.]{7,15}/', $ip, $ips)) {
			$ip = $ips[0];
		}

		return $ip;
	}

	/**
	 * Get client user-agent
	 *
	 * @return string
	 */
	public function userAgent() {
		return $this->server('HTTP_USER_AGENT');
	}

	/**
	 * Get connection in Workerman
	 *
	 * @return \Workerman\Connection\TcpConnection
	 */
	public function connection() {
		return $this->connection;
	}

}
