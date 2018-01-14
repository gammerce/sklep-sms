<?php

use App\Translator;
use Install\InstallManager;

if (!defined('IN_SCRIPT')) {
    exit;
}

require __DIR__ . '/../../bootstrap/autoload.php';

$lang = new Translator();

set_exception_handler([InstallManager::instance(), 'handleException']);
