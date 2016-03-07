<?php

use \Workerman\Protocols\Http;

function nvExit($status='') {
	if ($status) {
		echo $status;
	}

	throw new ExitException();
}

function nvHeader($string, $replace=true, $http_response_code=null) {
	Http::header($string, $replace, $http_response_code);
}

/**
 * Throw 404 Page Not Found
 *
 * @param int $status 404 status<br />
 * 0 controller file not found<br />
 * 1 controller class not found<br />
 * 2 action not found<br />
 * 3 user custom status
 * @throws ExitException
 */
function nv404($status=3) {
	nvHeader('HTTP/1.1 404 Not Found');
	nvExit('404 Page Not Found.');
}

class ExitException extends Exception {}

