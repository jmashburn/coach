<?php

include_once 'module/Application/src/functions.php';

define('ROOT_DIR', dirname(__FILE__));

$loader = include (ROOT_DIR . '/vendor/autoload.php');
$config = \Config::getConfig('routes');
return $config;
