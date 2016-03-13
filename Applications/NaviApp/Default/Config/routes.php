<?php

return array(
	'static/.*' => 'test/staticout',
	'overload(/.+)?' => 'test/routes/$1',
	'input' => 'test/input'
);
