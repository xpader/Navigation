<?php

namespace Wide\Controller;

use Navigation\Library\Sendfile;
use Navigation\Navi;

class Staticfile extends \Controller {

	/**
	 * @var Sendfile
	 */
	private static $sender = null;

	public function __construct() {
		parent::__construct();

		if (self::$sender === null) {
			self::$sender = new Sendfile();
			self::$sender->useETag = true;
			self::$sender->cacheControl = true;
			self::$sender->use304status = true;
		}
	}

	/**
	 * Output static file
	 *
	 * @throws \ExitException
	 */
	public function index() {
		$uri = $this->input->server('REQUEST_URI');

		//remove query string
		$query = strpos($uri, '?');
		if ($query !== false) {
			$uri = substr($uri, 0, $query);
		}

		if (substr($uri, 0, 8) == '/static/') {
			$path = substr($uri, 8); //strip /static/
		} else {
			$path = substr($uri, 1); //ignore left slash
		}

		//security
		if (strpos($path, '..') !== false) {
			nvHeader('HTTP/1.1 400 Bad Request');
			nvExit('<h1>400 Bad Request</h1>');
		}

		$file = RUN_DIR.'/static/'.$path;

		self::$sender->send($file);
	}

}
