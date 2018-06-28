<?php

require __DIR__ . '/../vendor/autoload.php';

if (!defined('SCRIPT_ROOT')) {
    define('SCRIPT_ROOT', dirname(__DIR__) . "/");
}

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', "ss_");
}

if (!defined('SQL_LOG')) {
    define('SQL_LOG', SCRIPT_ROOT . "errors/sql.log");
}

if (!defined('ERROR_LOG')) {
    define('ERROR_LOG', SCRIPT_ROOT . "errors/errors.log");
}

if (!defined('VERSION')) {
    define('VERSION', "3.4.2");
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
