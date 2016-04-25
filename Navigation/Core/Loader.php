<?php

namespace Navigation\Core;

class Loader {

	protected $__nvObjectMaps = array();
	protected $__nvViewPath = '';
	protected $__nvViewVars = array();

	public function __construct() {
		$NV =& getInstance();
		$currentApp = $NV->config->getCurrentApp();
		$this->__nvViewPath = $currentApp['path'].DIRECTORY_SEPARATOR.'View'.DIRECTORY_SEPARATOR;
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
			$objName = $rename != '' ? $rename : (is_int($i) ? $arr[count($arr) - 1] : $i);

			$arr = array_map('ucfirst', $arr);

			switch ($arr[0]) {
				case 'Lib': $arr[0] = 'Library'; break;
				case 'Mod': $arr[0] = 'Model'; break;
			}

			//1 means Library, 2 means other class
			$mark = $arr[0] == 'Library' ? 1 : 2;
			$this->registerObject($objName, $mark.join('\\', $arr));
		}
	}

	/**
	 * Load model
	 *
	 * @param array|string $models
	 * @param string $instance
	 */
	public function model($models, $instance='') {
		if (!is_array($models)) {
			$instance === '' && $instance = 0;
			$models = array($instance=>$models);
		}

		foreach ($models as $i => $model) {
			$instance = is_int($i) ? '' : $i;
			$this->import('Model/'.$model, $instance);
		}
	}

	public function view($name, $vars=null, $return=false) {
		$viewFile = $this->__nvViewPath.$name.'.php';
		return $this->parseView($viewFile, $name, $vars, $return);
	}

	/**
	 * Load View Vars
	 *
	 * @param array|object|string $vars
	 * @param mixed $value
	 * @return void
	 */
	public function vars($vars, $value=NULL) {
		if ((is_array($vars) || is_object($vars))) {
			$this->__nvViewVars = array_merge($this->__nvViewVars, (array)$vars);
		} else {
			$this->__nvViewVars[$vars] = $value;
		}
	}

	/**
	 * Parse view
	 *
	 * @param string $__nvViewFile View file path
	 * @param string $__nvViewName View name
	 * @param array|object $__nvVars View vars to assign
	 * @param bool $__nvReturnBuffer Return view buffer and not output
	 * @return string
	 */
	protected function parseView($__nvViewFile, $__nvViewName, $__nvVars=null, $__nvReturnBuffer=false) {
		$this->vars($__nvVars);

		if (!is_file($__nvViewFile)) {
			nvCallError("No Found View File '".$__nvViewName."'");
		}

		//exclude some elements and may have cycle reference objects
		$NV = getInstance();
		$objectNames = array_diff(array_keys(get_object_vars($NV)), array('__nvObjects'));

		foreach ($objectNames as $key) {
			if (!isset($this->$key)) {
				$this->$key =& $NV->$key;
			}
		}

		unset($NV, $objectNames, $key);

		//extract view vars
		extract($this->__nvViewVars, EXTR_OVERWRITE);

		ob_start();

		include $__nvViewFile;
		//nvLog("Include View File '$__nvViewName'");

		//Resolve cycle reference
		unset($this->load);

		if($__nvReturnBuffer) {
			$buffer = ob_get_contents();
			ob_end_clean();
			//nvLog("Return Buffer From View '$__nvViewName'");
			return $buffer;
		} else {
			ob_end_flush();
		}
	}

	/**
	 * Register an object to object maps
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return bool Is register success? user can not register core object name
	 */
	protected function registerObject($key, $data) {
		if (isset($this->__nvObjectMaps[$key]) && $this->__nvObjectMaps[$key] === 0) {
			return false;
		}

		$this->__nvObjectMaps[$key] = $data;
		return true;
	}

	public function isObjectRegistered($key) {
		return isset($this->__nvObjectMaps[$key]);
	}

	public function getRegisteredObject($key) {
		if (isset($this->__nvObjectMaps[$key])) {
			$row = $this->__nvObjectMaps[$key];

			if ($row === 0) {
				return array(0, 'core');
			} else {
				switch (substr($row, 0, 1)) {
					case 1: return array(1, substr($row, 1)); break; //library
					case 2: return array(2, substr($row, 1)); break; //other class
					default:
						return $row;
				}
			}
		} else {
			return null;
		}
	}

	public function showMaps() {
		print_r($this->__nvObjectMaps);
	}

	public function database() {

	}

}
