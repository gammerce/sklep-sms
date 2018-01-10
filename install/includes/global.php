<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

// Emulujemy wersję PHP
if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

$working_dir = dirname(dirname(dirname(__FILE__)));
require_once $working_dir . "/includes/init.php";
require_once SCRIPT_ROOT . "includes/class_template.php";
require_once SCRIPT_ROOT . "includes/functions.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";
require_once SCRIPT_ROOT . "includes/mysqli.php";
require_once SCRIPT_ROOT . "includes/class_translator.php";
require_once SCRIPT_ROOT . "install/includes/functions.php";

set_exception_handler("exceptionHandler");
