<?php

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

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
    define('VERSION', "3.3.9");
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

$app = Container::getInstance();

try {
    (new Dotenv(SCRIPT_ROOT . "confidential"))->load();
} catch (InvalidPathException $e) {
    //
}

$app->singleton(Database::class, function () {
    $db = new Database(
        getenv('DB_HOST'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        getenv('DB_DATABASE')
    );
    $db->query("SET NAMES utf8");

    return $db;
});
