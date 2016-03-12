<?php

namespace Navigation\Core;

class Loader {

	private $objectMaps = array();

	public function __construct() {
		/*
		foreach ($initObjectsName as $key) {
			$this->registerObject($key, 0); //0 means Core Object
		}
		*/
	}

	public function import($name, $rename='') {
		if (is_array($name)) {
			if ($rename != '') {
				nvCallError("Can not rename to $rename when import from array");
			}
		} else {
			$name = array($name);
		}

		foreach ($name as $i => $row) {
			if (strpos($row, '/') === false) {
				nvCallError("Could not import $row, name error");
			}

			$arr = explode('/', $row);
			$rename = $arr[count($arr) - 1];

			$arr = array_map('ucfirst', $arr);

			switch ($arr[0]) {
				case 'Lib': $arr[0] = 'Library'; break;
				case 'Mod': $arr[0] = 'Model'; break;
			}

			//1 means Library, 2 means other class
			$mark = $arr[0] == 'Library' ? 1 : 2;
			$this->registerObject($rename, $mark.join('\\', $arr));
		}
	}

	/**
	 * Register an object to object maps
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return bool Is register success? user can not register core object name
	 */
	public function registerObject($key, $data) {
		if (isset($this->objectMaps[$key]) && $this->objectMaps[$key] === 0) {
			return false;
		}

		$this->objectMaps[$key] = $data;
		return true;
	}

	public function isObjectRegistered($key) {
		return isset($this->objectMaps[$key]);
	}

	public function getRegisteredObject($key) {
		if (isset($this->objectMaps[$key])) {
			$row = $this->objectMaps[$key];

			if ($row === 0) {
				return [0, 'core'];
			} else {
				switch (substr($row, 0, 1)) {
					case 1: return [1, substr($row, 1)]; break;
					case 2: return [2, substr($row, 1)]; break;
					default:
						return $row;
				}
			}
		} else {
			return null;
		}
	}

	public function showMaps() {
		print_r($this->objectMaps);
	}

}
