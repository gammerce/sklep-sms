<?php

use App\ShopState;
use App\Translator;
use Install\InstallManager;

if (!defined('IN_SCRIPT')) {
    exit;
}

require __DIR__ . '/../../bootstrap/autoload.php';

/** @var Translator $lang */
$lang = app()->make(Translator::class);

set_exception_handler([app()->make(InstallManager::class), 'handleException']);

if (ShopState::isInstalled() && app()->make(ShopState::class)->isUpToDate()) {
    exit;
}