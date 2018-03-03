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

// Te interfejsy są potrzebne do klas różnego rodzajów
foreach (scandir("$scriptRoot/includes/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/interfaces/" . $file;
    }
}

// Dodajemy klasy wszystkich modulow platnosci
foreach (scandir("$scriptRoot/includes/verification/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/verification/interfaces/" . $file;
    }
}

foreach (scandir("$scriptRoot/includes/verification") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/verification/" . $file;
    }
}


// Dodajemy klasy wszystkich usług
require_once "$scriptRoot/includes/services/service.php";

// Pierwsze ładujemy interfejsy
foreach (scandir("$scriptRoot/includes/services/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/services/interfaces/" . $file;
    }
}

foreach (scandir("$scriptRoot/includes/services") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/services/" . $file;
    }
}


// Dodajemy klasy wszystkich bloków
require_once "$scriptRoot/includes/blocks/block.php";
foreach (scandir("$scriptRoot/includes/blocks") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/blocks/" . $file;
    }
}


// Dodajemy klasy wszystkich stron
require_once "$scriptRoot/includes/pages/page.php";
require_once "$scriptRoot/includes/pages/pageadmin.php";

// Pierwsze ładujemy interfejsy
foreach (scandir("$scriptRoot/includes/pages/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/pages/interfaces/" . $file;
    }
}

foreach (scandir("$scriptRoot/includes/pages") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/pages/" . $file;
    }
}

foreach (scandir("$scriptRoot/includes/entity") as $file) {
    if (ends_at($file, ".php")) {
        require_once "$scriptRoot/includes/entity/" . $file;
    }
}
