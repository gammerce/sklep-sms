<?php

if (!defined("IN_SCRIPT")) {
    die("Nie ma tu nic ciekawego.");
}

if (!defined('SCRIPT_ROOT')) {
    define('SCRIPT_ROOT', dirname(dirname(__FILE__)) . "/");
}

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', "ss_");
}

if (!defined('SQL_LOG')) {
    define('SQL_LOG', SCRIPT_ROOT . "errors/sql.log");
}

if (!defined('VERSION')) {
    define('VERSION', "3.2.1");
}