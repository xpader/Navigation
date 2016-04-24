<?php

namespace Wide\Model;

class Test extends \Model {

	public function __construct() {
		echo '<p>Test Model Construct</p>';
	}

	public function iam() {
		return 'I am model!';
	}

}
