<?php

namespace Navigation\Core;

class Input {

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
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}

}
