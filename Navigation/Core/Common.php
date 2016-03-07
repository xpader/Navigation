<?php

use \Workerman\Protocols\Http;

function nvExit() {
	throw new ExitException();
}

function nvHeader($string, $replace=true, $http_response_code=null) {
	Http::header($string, $replace, $http_response_code);
}

function nv404() {
	nvHeader('HTTP/1.1 404 Not Found');
	echo '404 Page Not Found.';
	nvExit();
}

class ExitException extends Exception {}

