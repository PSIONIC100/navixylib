<?php

require_once __DIR__.'/../vendor/autoload.php';

use Telemovilperu\Navixylib\Navixy;

$api = new Navixy(dirname(__DIR__));
echo $api->loginPanel();
echo PHP_EOL;
echo $api->loginUser(5);
echo PHP_EOL;
echo $api->getPosition(50,5);


