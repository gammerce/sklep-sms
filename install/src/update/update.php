<?php

use Install\DatabaseMigration;
use Install\InstallManager;

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/src/update/includes/global.php";

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

/** @var InstallManager $installManager */
$installManager = app()->make(InstallManager::class);

$installManager->start();

/** @var DatabaseMigration $migrator */
$migrator = app()->make(DatabaseMigration::class);
$migrator->update();

$installManager->finish();

json_output('ok', "Instalacja przebiegła pomyślnie.", true);
