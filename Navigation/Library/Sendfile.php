<?php

namespace Navigation\Library;

class Sendfile {

	/**
	 * Use ETag cache match
	 *
	 * @var bool
	 */
	public $useETag = false;

	/**
	 * Use Cache-Control and Expires
	 *
	 * @var bool
	 */
	public $cacheControl = false;

	/**
	 * @var bool
	 */
	public $use304status = false;

	/**
	 * Server Tag
	 *
	 * @var string
	 */
	public $serverTag = 'Workerman/3.0';

	/*
	protected $expires;
	protected $mimeTypes;

	public function __construct() {
		$NV =& getInstance();
		$this->expires = $NV->config->load('static_cache', true, true);
		$this->mimeTypes = $NV->config->load('mime_types', true, true);
	}
	*/

	/**
	 * Send a file to client
	 *
	 * @param string $file
	 * @param array $headers Add headers to response, can not override exists headers
	 * @throws \ExitException
	 */
	public function send($file, array $headers=array()) {
		if (!is_file($file)) {
			nv404();
		}

		$time = time();
		$headers['Date'] = gmdate('D, d M Y H:i:s', $time) . ' GMT';
		$NV =& getInstance();

		//文件类型信息
		$pathInfo = pathinfo($file);

		//文件是否有缓存有效期
		if ($this->cacheControl && isset($pathInfo['extension'])) {
			$expires = $NV->config->get($pathInfo['extension'], 'static_cache');

			if ($expires) {
				switch (substr($expires, -1)) {
					case 'm': $expires = substr($expires, 0, -1) * 60; break;
					case 'h': $expires = substr($expires, 0, -1) * 3600; break;
					case 'd': $expires = substr($expires, 0, -1) * 86400; break;
				}

				$headers['Expires'] = gmdate('D, d M Y H:i:s', $expires + $time).' GMT';
				$headers['Cache-Control'] = 'max-age='.$expires;
			}
		}

		//获取文件信息
		$info = stat($file);
		$connection = $NV->input->connection();

		//客户端传来文件时间未变化则直接 304
		//客户端传来的 ETag 未变化也 304
		if ($this->use304status) {
			$modifiedTime = $info ? gmdate('D, d M Y H:i:s', $info['mtime']) . ' GMT' : '';
			$modifiedTime && $headers['Last-Modified'] = $modifiedTime;

			//ETag
			if ($this->useETag) {
				$etag = $this->getETag($file, $info['mtime'], $info['size']);
				$headers['ETag'] = $etag;
			}

			if (
				(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $modifiedTime === $_SERVER['HTTP_IF_MODIFIED_SINCE'])
				|| ($this->useETag && !empty($_SERVER['HTTP_IF_NONE_MATCH']) && $etag === $_SERVER['HTTP_IF_NONE_MATCH'])
			) {
				$this->sendHeaders($connection, 304, $headers);
				throw new \ExitException('RAW_OUTPUT_BREAK', 5);
			}
		}

		//Content-Type
		if (isset($pathInfo['extension'])) {
			$mimeType = $NV->config->get($pathInfo['extension'], 'mime_types');
			$mimeType && $headers['Content-Type'] = $mimeType;
		}

		$headers['Content-Length'] = $info['size'];

		$this->sendHeaders($connection, 200, $headers);

		$connection->pause = false;
		$connection->fp = fopen($file, 'rb');

		$send = function() use ($connection) {
			while (!$connection->pause) {
				$buffer = fread($connection->fp, 8192);

				// 读不到数据说明文件读到末尾了
				if ($buffer === '' || $buffer === false) {
					$connection->onBufferDrain = $connection->onBufferFull = null;
					$connection->pause = true;
					fclose($connection->fp);
					return;
				}

				$connection->send($buffer, true);
			}
		};

		//缓冲区满时，暂停发送
		$connection->onBufferFull = function($connection) {
			$connection->pause = true;
		};

		//缓冲区空出时继续恢复发送
		$connection->onBufferDrain = function($connection) use ($send) {
			$connection->pause = false;
			$send();
		};

		//开始发送文件数据
		$send();

		//Tell output that has been break by raw data, and stop global output
		throw new \ExitException('RAW_OUTPUT_BREAK', 5);
	}

	/**
	 * Sort and send headers
	 *
	 * @param \Workerman\Connection\TcpConnection $connection
	 * @param int $statusCode
	 * @param array $headers
	 */
	private function sendHeaders($connection, $statusCode, array $headers) {
		switch ($statusCode) {
			case 200: $out = 'HTTP/1.1 200 OK'; break;
			case 304: $out = 'HTTP/1.1 304 Not Modified'; break;
			default: $out = 'HTTP/1.1 400 Bad Request';
		}

		$sort = array('Date', 'Content-Type', 'Content-Length', 'Connection', 'Last-Modified', 'Expires', 'Cache-Control', 'ETag');

		if (!isset($headers['Date'])) {
			$headers['Date'] = gmdate('D, d M Y H:i:s') . ' GMT';
		}

		if (!isset($headers['Connection'])) {
			$headers['Connection'] = 'keep-alive';
		}

		//header 排序
		foreach ($sort as $name) {
			if (isset($headers[$name])) {
				$out .= "\r\n$name: {$headers[$name]}";
				unset($headers[$name]);
			}
		}

		//其它 header
		foreach ($headers as $name => $value) {
			$out .= "\r\n$name: $value";
		}

		//add server tag
		if ($this->serverTag) {
			$out .= "\r\nServer: {$this->serverTag}";
		}

		$out .= "\r\n\r\n";

		$connection->send($out, true);
	}

	/**
	 * Get file ETag
	 *
	 * @param string $file File full path
	 * @param int $filemtime Last modified time
	 * @param int $filesize
	 * @return string
	 */
	private function getETag($file, $filemtime, $filesize) {
		$meta = $file."\t".$filemtime."\t".$filesize;
		return '"'.substr(md5($meta), 8, 16).'"';
	}

}
