<?php

use App\ShopState;

error_reporting(E_ALL);
ini_set("display_errors", 1);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install");

require_once "src/global.php";

$db = DBInstance::get();

if ($db === null) {
    require SCRIPT_ROOT . "install/src/full/view.php";
}

$shopState = new ShopState($db);
if (!$shopState->isUpToDate()) {
    require SCRIPT_ROOT . "install/src/update/view.php";
}

output_page("Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install");
