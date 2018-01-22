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
    define('VERSION', "3.4.1");
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (!defined('TYPE_NICK')) {
    define('TYPE_NICK', 1 << 0);
}

if (!defined('TYPE_IP')) {
    define('TYPE_IP', 1 << 1);
}

if (!defined('TYPE_SID')) {
    define('TYPE_SID', 1 << 2);
}

// Te interfejsy są potrzebne do klas różnego rodzajów
foreach (scandir(SCRIPT_ROOT . "includes/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/interfaces/" . $file;
    }
}

// Dodajemy klasy wszystkich modulow platnosci
foreach (scandir(SCRIPT_ROOT . "includes/verification/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/verification/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/verification") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/verification/" . $file;
    }
}


// Dodajemy klasy wszystkich usług
require_once SCRIPT_ROOT . "includes/services/service.php";

// Pierwsze ładujemy interfejsy
foreach (scandir(SCRIPT_ROOT . "includes/services/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/services/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/services") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/services/" . $file;
    }
}


// Dodajemy klasy wszystkich bloków
require_once SCRIPT_ROOT . "includes/blocks/block.php";
foreach (scandir(SCRIPT_ROOT . "includes/blocks") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/blocks/" . $file;
    }
}


// Dodajemy klasy wszystkich stron
require_once SCRIPT_ROOT . "includes/pages/page.php";
require_once SCRIPT_ROOT . "includes/pages/pageadmin.php";

// Pierwsze ładujemy interfejsy
foreach (scandir(SCRIPT_ROOT . "includes/pages/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/pages/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/pages") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/pages/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/entity") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/entity/" . $file;
    }
}
