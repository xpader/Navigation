<?php

namespace Wide\Controller;

class Index extends \Controller {

	public function index() {
		$this->load->vars('date', date('Y-m-d H:i:s'));
		$this->load->view('welcome');
	}

}