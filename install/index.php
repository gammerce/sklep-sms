<?php

use App\ShopState;
use Install\OldShop;

error_reporting(E_ALL);
ini_set("display_errors", 1);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install");

require_once "src/global.php";

/** @var OldShop $oldShop */
$oldShop = app()->make(OldShop::class);
$oldShop->checkForConfigFile();

if (!ShopState::isInstalled()) {
    require SCRIPT_ROOT . "install/src/full/view.php";
}

/** @var ShopState $shopState */
$shopState = app()->make(ShopState::class);
if (!$shopState->isUpToDate()) {
    require SCRIPT_ROOT . "install/src/update/view.php";
}

output_page("Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install");
