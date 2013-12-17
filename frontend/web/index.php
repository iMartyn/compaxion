<?php
$f3=require('../fatfree/lib/base.php');
$f3->route('GET /',
	function() {
		echo 'Hello, world.';
	}
);
$f3->route('GET /members',
	function() {
		echo 'Hello members.';
	}
);
$f3->run();
