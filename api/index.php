<?php

require_once('epiphany/src/Epi.php');

Epi::setPath('base', dirname(__FILE__).'/epiphany/src');
Epi::init('api');

getRoute()->get('/', 'helloWorld');
getRoute()->run();

function helloWorld() {
	echo "Hello World";
}

