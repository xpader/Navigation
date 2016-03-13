<?php

return array(
	'static/' => 'STATIC '.realpath(__DIR__.'/../../../../static/'),
	'overload(/.+)?' => 'test/routes/$1',
	'input' => 'test/input'
);
