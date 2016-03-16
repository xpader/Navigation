<?php

return array(
	'(static/.*|favicon.ico)' => 'staticfile',
	'overload(/.+)?' => 'test/routes/$1',
	'input' => 'test/input'
);
