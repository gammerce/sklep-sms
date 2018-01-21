<?php

use App\Translator;
use Install\InstallManager;

// TODO: Refactor install scripts
if (!defined('IN_SCRIPT')) {
    exit;
}

require __DIR__ . '/../../bootstrap/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

/** @var Translator $lang */
$lang = $app->make(Translator::class);

set_exception_handler([$app->make(InstallManager::class), 'handleException']);
