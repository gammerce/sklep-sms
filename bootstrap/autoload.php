<?php

require __DIR__ . '/../vendor/autoload.php';

$scriptRoot = dirname(__DIR__);

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', "ss_");
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

foreach (scandir("$scriptRoot/includes/entity") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/entity/" . $file;
    }
}
