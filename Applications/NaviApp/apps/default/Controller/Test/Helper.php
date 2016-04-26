<?php

namespace Wide\Controller\Test;

use Wide\Helper\Common;

class Helper extends \Controller {

	public function index() {
		print_r(get_included_files());
	}

	public function common() {
		echo Common::test();
	}

}
