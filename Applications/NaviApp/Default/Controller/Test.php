<?php

namespace Wide\Controller;

class PC extends \Controller {

	private $bbb = 2;

}

class Test extends PC {

	public function index() {
		echo 'This is test controller.<br />';

		if (class_exists('\\Wide\\Model\\Test')) {
			$obj = new \Wide\Model\Test();
			echo $obj->iam();

			$obj2 = new \Wide\Model\Test();
			echo $obj2->iam();
		}
	}

	public function newWorld($a=1,$b=2) {
		echo 'Hello World in Test Controller.';
		echo '<br />';
		echo $a.'-'.$b;
	}

	public function ivkmodel() {
		$m = new \Wide\Model\Test();

		echo '<pre>';
		print_r($m);
		echo '</pre>';
	}

	public function config() {
		//$apps = $this->config->loadConfig();

		var_dump($this->config->get('servlet'));

	}

	public function updateConfig() {
		$this->config->set('servlet', "just a test\nupdate in ".date('Y-m-d H:i:s'));

		//echo '---done---';
	}

	public function loader() {
		$this->load->import(['mod/test', 'library/jovi']);

		//$this->load->showMaps();

		echo $this->test->iam();

		echo "<br />\n";

		echo $this->abc;

		$this->jovi->bon();

		//$this->jovi->cc();

		//echo $struct;

		echo 'Script is still running.';
	}

	public function printserver() {
		echo '<pre>';
		print_r($_SERVER);
		echo '</pre>';
	}

	public function input() {
		echo $this->input->ip();
		echo '<br />';
		echo $this->input->userAgent();
		echo '<br />';
		print_r($this->input->uri());
		echo '<br />';
	}

	public function routes($p='') {

	}

	/**
	 * 发送静态文件
	 *
	 * @throws \ExitException
	 */
	public function staticout() {
		$uri = $this->input->server('REQUEST_URI');
		$path = substr($uri, strlen('/static/'));
		$query = strpos($path, '?');

		//remove query string
		if ($query !== false) {
			$path = substr($path, 0, $query);
		}

		//security
		if (strpos($path, '..') !== false) {
			nvHeader('HTTP/1.1 400 Bad Request');
			nvExit('<h1>400 Bad Request</h1>');
		}

		$file = RUN_DIR.'/static/'.$path;

		self::sendfile($file);
	}

	/**
	 * Send a file to client
	 *
	 * @param string $file
	 * @throws \ExitException
	 */
	private static function sendfile($file) {
		if (!is_file($file)) {
			nv404();
		}

		$NV =& getInstance();
		$connection = $NV->input->connection();

		// 获取文件信息
		$info = stat($file);
		$modifiedTime = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';

		// 客户端传来文件时间未变化则直接 304
		if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info && $modifiedTime === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
			nvHeader('HTTP/1.1 304 Not Modified');
			$modifiedTime && nvHeader("Last-Modified: $modifiedTime");
			return;
		}

		$header = "HTTP/1.1 200 OK\r\nConnection: keep-alive\r\nContent-Length: {$info['size']}\r\n";

		//Mimetype 信息
		$pathInfo = pathinfo($file);
		$mimeType = null;

		if (isset($pathInfo['extension'])) {
			$mimeType = $NV->config->get($pathInfo['extension'], 'mime_types');
			$header .= "Content-Type: $mimeType\r\n";
		}

		$modifiedTime && $header .= "Last-Modified: $modifiedTime\r\n";
		$header .= "Server: Workerman/3.0\r\n\r\n";

		$connection->send($header, true);

		$connection->fp = fopen($file, 'rb');
		$connection->pause = false;

		$send = function() use ($connection) {
			// 对应客户端的连接发送缓冲区未满时
			while (!$connection->pause) {
				// 从磁盘读取文件
				$buffer = fread($connection->fp, 8192);

				// 读不到数据说明文件读到末尾了
				if ($buffer === '' || $buffer === false) {
					$connection->onBufferDrain = null;
					$connection->onBufferFull = null;
					$connection->pause = true;
					fclose($connection->fp);
					return;
				}

				$connection->send($buffer, true);
			}
		};

		$connection->onBufferFull = function($connection) {
			$connection->pause = true;
		};

		$connection->onBufferDrain = function($connection) use ($send) {
			$connection->pause = false;
			$send();
		};

		$send();

		//Tell output that has been break by raw data, and stop global output
		throw new \ExitException('RAW_OUTPUT_BREAK', 5);
	}

}
