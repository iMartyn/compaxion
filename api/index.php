<?php

require_once('epiphany/src/Epi.php');
require_once('classes/space.php');
require_once('classes/notReallyStatic.php');

Epi::setPath('base', dirname(__FILE__) . '/epiphany/src');
Epi::init('api');

getRoute()->get('/', 'helloWorld');
getRoute()->get('/space/status', array('notReallyStaticSpace', 'statusHTML'));
getRoute()->get('/space/status.json', array('notReallyStaticSpace', 'statusJSON'));
getRoute()->run();

function helloWorld() {
    echo "Hello World";
}

