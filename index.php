<?php

use classes\Core;

spl_autoload_register(function ($class_name) {
    $path = str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($path)) {
        include_once $path;
    }
});

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$core = Core::getInstance();
$core->Init();
$core->run();
//$core->done();
