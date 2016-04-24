<?php

use \Workerman\Protocols\Http;

function nvExit($status=null) {
	$statusCode = null;

	if (is_int($status)) {
		$statusCode = $status;
	} elseif ($status) {
		echo $status;
	}

	throw new ExitException('', $statusCode);
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

function &getInstance() {
	return \Controller::getInstance();
}

function import($name) {
	$NV =& getInstance();
	$NV->load->import($name);
}

/**
 * Return Single Instance
 *
 * @param string $class Class name with full namespace
 * @return Object
 */
function instance($class) {
	$NV =& getInstance();

	$key = strtolower($class);
	$key = ltrim($key, '\\');

	//The object must save in dynamic controller
	//that when request finished, the object can be collection with controller
	if (!isset($NV->__nvObjects[$key])) {
		$NV->__nvObjects[$key] = new $class;
	}

	return $NV->__nvObjects[$key];
}

function nvCallError($message, $errorType=E_USER_ERROR) {
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	$trace = $trace[1];
	trigger_error("__NAVI_ERROR__\n$message\n{$trace['file']}\n{$trace['line']}", $errorType);
}

function nvLog($flag, $message) {


}

/**
 * Navi error handler
 *
 * @param int $errno
 * @param string $message
 * @param string $file
 * @param int $line
 * @return bool|void
 */
function _nvErrorHandler($errno, $message, $file, $line) {
	if ($errno == E_STRICT) {
		return;
	}

	//Is should display in error_reporting setting
	if (($errno & error_reporting()) == $errno) {
		static $levels = array(
			E_ERROR				=>	'Error',
			E_WARNING			=>	'Warning',
			E_PARSE				=>	'Parsing Error',
			E_NOTICE			=>	'Notice',
			E_CORE_ERROR		=>	'Core Error',
			E_CORE_WARNING		=>	'Core Warning',
			E_COMPILE_ERROR		=>	'Compile Error',
			E_COMPILE_WARNING	=>	'Compile Warning',
			E_USER_ERROR		=>	'User Error',
			E_USER_WARNING		=>	'User Warning',
			E_USER_NOTICE		=>	'User Notice',
			E_STRICT			=>	'Runtime Notice'
		);

		//Return error to system process
		if (!isset($levels[$errno])) {
			return false;
		}

		//Navi get error specil reporting
		if (($errno == E_USER_NOTICE || $errno == E_USER_ERROR || $errno == E_USER_WARNING)
			&& substr($message, 0, 14) == '__NAVI_ERROR__') {

			$errno == E_USER_NOTICE && $errno = E_NOTICE;

			list(, $message, $file, $line) = explode("\n", $message);
		}

		echo "<br /><!--NVERROR-->\n<b>{$levels[$errno]}</b>:  $message in <b>$file</b> on line <b>$line</b><br />";

		$errno == E_USER_ERROR && nvExit();
	}


}

/*
function _nvExceptionHandler() {
	echo "Navi Exception Handler:";
	print_r(func_get_args());
}
*/

function _nvShutdownHandler() {
	call_user_func_array('_nvErrorHandler', error_get_last());
	exit(1);
}

class ExitException extends Exception {}

