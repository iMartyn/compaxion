<?php

require_once('epiphany/src/Epi.php');
require_once('classes/space.php');

Epi::setPath('base', dirname(__FILE__).'/epiphany/src');
Epi::init('api');

getRoute()->get('/', 'helloWorld');
getRoute()->get('/space/status',array('Space','statusHTML'));
getRoute()->get('/space/status.json',array('Space','statusJSON'));
getRoute()->run();

function helloWorld() {
	echo "Hello World";
}

