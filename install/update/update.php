<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/update/includes/global.php";
require_once SCRIPT_ROOT . "install/includes/DatabaseMigration.php";
require_once SCRIPT_ROOT . "install/includes/InstallManager.php";

$db = DBInstance::get();

$everything_ok = true;
$update_info = update_info($everything_ok);

// Nie wszystko jest git
if (!$everything_ok) {
    json_output(
        "warnings",
        "Aktualizacja nie mogła zostać przeprowadzona. Nie wszystkie warunki są spełnione.",
        false,
        [
            'update_info' => $update_info,
        ]
    );
}

// -------------------- INSTALACJA --------------------

InstallManager::instance()->start();

$db->query("SET NAMES utf8");

$migrator = new DatabaseMigration($db, $lang);
$migrator->update();

InstallManager::instance()->finish();

json_output('ok', "Instalacja przebiegła pomyślnie.", true);
