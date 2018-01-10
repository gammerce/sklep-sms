<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install");

require_once "includes/global.php";

$db = DBInstance::get();

if ($db === null) {
    require_once SCRIPT_ROOT . "install/full/view.php";
}

$shopState = new ShopState($db);
if (!$shopState->isUpToDate()) {
    require_once SCRIPT_ROOT . "install/update/view.php";
}

output_page("Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install");
