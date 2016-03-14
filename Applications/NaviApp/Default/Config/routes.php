<?php

return array(
	'static/.*' => 'staticfile/index',
	'overload(/.+)?' => 'test/routes/$1',
	'input' => 'test/input'
);
