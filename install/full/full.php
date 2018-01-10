<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/full/includes/global.php";
require_once SCRIPT_ROOT . "install/includes/DatabaseMigration.php";
require_once SCRIPT_ROOT . "install/includes/InstallManager.php";

try {
    $db = new Database($_POST['db_host'], $_POST['db_user'], $_POST['db_password'], $_POST['db_db']);
    DBInstance::set($db);
} catch (SqlQueryException $e) {
    output_page($lang->translate('mysqli_' . $e->getMessage()) . "\n\n" . $e->getError());
}

$warnings = [];

// Licencja ID
if (!strlen($_POST['license_id'])) {
    $warnings['license_id'][] = "Nie podano ID licencji.";
}

// Licencja hasło
if (!strlen($_POST['license_password'])) {
    $warnings['license_password'][] = "Nie podano hasła licencji.";
}

// Admin nick
if (!strlen($_POST['admin_username'])) {
    $warnings['admin_username'][] = "Nie podano nazwy dla użytkownika admin.";
}

// Admin hasło
if (!strlen($_POST['admin_password'])) {
    $warnings['admin_password'][] = "Nie podano hasła dla użytkownika admin.";
}

foreach ($files_priv as $file) {
    if (!strlen($file)) {
        continue;
    }

    if (!is_writable(SCRIPT_ROOT . '/' . $file)) {
        $warnings['general'][] = "Ścieżka <b>" . htmlspecialchars($file) . "</b> nie posiada praw do zapisu.";
    }
}

// Sprawdzamy ustawienia modułuów
foreach ($modules as $module) {
    if (!$module['value'] && $module['must-be']) {
        $warnings['general'][] = "Wymaganie: <b>{$module['text']}</b> nie jest spełnione.";
    }
}

// Jeżeli są jakieś błedy, to je zwróć
if (!empty($warnings)) {
    // Przerabiamy ostrzeżenia, aby lepiej wyglądały
    foreach ($warnings as $brick => $warning) {
        if (empty($warning)) {
            continue;
        }

        if ($brick != "general") {
            $warning = create_dom_element("div", implode("<br />", $warning), [
                'class' => "form_warning",
            ]);
        }

        $return_data['warnings'][$brick] = $warning;
    }

    json_output("warnings", $lang->translate('form_wrong_filled'), false, $return_data);
}

InstallManager::instance()->start();

$db->query("SET NAMES utf8");
$migrator = new DatabaseMigration($db, $lang);
$migrator->install(
    $_POST['license_id'], $_POST['license_password'], $_POST['admin_username'], $_POST['admin_password']
);

file_put_contents(SCRIPT_ROOT . "credentials/database.php",
    '<?php
$db_host = \'' . addslashes($_POST['db_host']) . '\';
$db_user = \'' . addslashes($_POST['db_user']) . '\';
$db_pass = \'' . addslashes($_POST['db_password']) . '\';
$db_name = \'' . addslashes($_POST['db_db']) . '\';'
);

InstallManager::instance()->finish();

json_output("ok", "Instalacja przebiegła pomyślnie.", true);
