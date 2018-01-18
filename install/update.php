<?php

use App\ShopState;

error_reporting(E_ALL);
ini_set("display_errors", 1);
@set_time_limit(0);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "ajax_install_update");

require_once "src/global.php";

if (ShopState::isInstalled() && app()->make(ShopState::class)->isUpToDate()) {
    exit;
}

require_once SCRIPT_ROOT . "install/src/update/update.php";
