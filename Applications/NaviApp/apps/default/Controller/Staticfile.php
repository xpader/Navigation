<?php

namespace Wide\Controller;

use Navigation\Library\Sendfile;
use Navigation\Navi;

class Staticfile extends \Controller {

	private static $sender = null;

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

		if (self::$sender === null) {
			$n = new Sendfile();
			$n->useETag = true;
			$n->cacheControl = true;
			$n->use304status = true;

			self::$sender = $n;
		}

		self::$sender->send($file, array('X-Powered-By'=>'Navi/'.Navi::VERSION));
	}

}
