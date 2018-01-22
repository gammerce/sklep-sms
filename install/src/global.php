<?php

use App\TranslationManager;
use Install\InstallManager;

// TODO: Refactor install scripts
if (!defined('IN_SCRIPT')) {
    exit;
}

require __DIR__ . '/../../bootstrap/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

/** @var TranslationManager $translationManager */
$translationManager = app()->make(TranslationManager::class);
$lang = $translationManager->user();

set_exception_handler([$app->make(InstallManager::class), 'handleException']);
