<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install");

require_once "global.php";

try {
	$db = new Database($_POST['db_host'], $_POST['db_user'], $_POST['db_password'], $_POST['db_db']);
} catch (SqlQueryException $e) {
	output_page($lang->mysqli[$e->getMessage()] . "\n\n" . $e->getError());
}

$warnings = array();

// Licencja ID
if (!strlen($_POST['license_id']))
	$warnings['license_id'][] = "Nie podano ID licencji.";

// Licencja hasło
if (!strlen($_POST['license_password']))
	$warnings['license_password'][] = "Nie podano hasła licencji.";

// Admin nick
if (!strlen($_POST['admin_username']))
	$warnings['admin_username'][] = "Nie podano nazwy dla użytkownika admin.";

// Admin hasło
if (!strlen($_POST['admin_password']))
	$warnings['admin_password'][] = "Nie podano hasła dla użytkownika admin.";

foreach ($files_priv as $file) {
	if (!strlen($file))
		continue;

	if (!is_writable(SCRIPT_ROOT . '/' . $file))
		$warnings['general'][] = "Ścieżka <b>" . htmlspecialchars($file) . "</b> nie posiada praw do zapisu.";
}

// Sprawdzamy ustawienia modułuów
foreach ($modules as $module) {
	if (!$module['value'] && $module['must-be'])
		$warnings['general'][] = "Wymaganie: <b>{$module['text']}</b> nie jest spełnione.";
}

// Jeżeli są jakieś błedy, to je zwróć
if (!empty($warnings)) {
	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	foreach ($warnings as $brick => $warning) {
		if (empty($warning))
			continue;

		if ($brick != "general") {
			$warning = create_dom_element("div", implode("<br />", $warning), array(
				'class' => "form_warning"
			));
		}

		$return_data['warnings'][$brick] = $warning;
	}

	json_output("warnings", $lang->form_wrong_filled, false, $return_data);
}

file_put_contents(SCRIPT_ROOT . "install/progress", '');

$queries = SplitSQL(SCRIPT_ROOT . "install/queries.sql");
$queries[] = $db->prepare(
	"UPDATE `" . TABLE_PREFIX . "settings` " .
	"SET `value`='%s' WHERE `key`='random_key';",
	array(get_random_string(16))
);
$queries[] = $db->prepare(
	"UPDATE `" . TABLE_PREFIX . "settings` " .
	"SET `value`='%s' WHERE `key`='license_login';",
	array($_POST['license_id'])
);
$queries[] = $db->prepare(
	"UPDATE `" . TABLE_PREFIX . "settings` " .
	"SET `value`='%s' WHERE `key`='license_password';",
	array(md5($_POST['license_password']))
);
$salt = get_random_string(8);
$queries[] = $db->prepare(
	"INSERT INTO `" . TABLE_PREFIX . "users` " .
	"SET `username` = '%s', `password` = '%s', `salt` = '%s', `regip` = '%s', `groups` = '2';",
	array($_POST['admin_username'], hash_password($_POST['admin_password'], $salt), $salt, get_ip())
);

$db->query("SET NAMES utf8");

// Wykonujemy zapytania, jedno po drugim
foreach ($queries as $query) {
	if (strlen($query)) {
		try {
			$db->query($query);
		} catch (SqlQueryException $e) {
			$input = array();
			$input[] = "Message: " . $lang->mysqli[$e->getMessage()];
			$input[] = "Error: " . $e->getError();
			$input[] = "Query: " . $e->getQuery(false);
			file_put_contents(SCRIPT_ROOT . 'errors/install.log', implode("\n", $input));
			file_put_contents(SCRIPT_ROOT . 'install/error', '');
			unlink(SCRIPT_ROOT . "install/progress");
			json_output('error', INSTALL_ERROR, false);
		}
	}
}

file_put_contents(SCRIPT_ROOT . "includes/config.php",
	'<?php
$db_host = \'' . addslashes($_POST['db_host']) . '\';
$db_user = \'' . addslashes($_POST['db_user']) . '\';
$db_pass = \'' . addslashes($_POST['db_password']) . '\';
$db_name = \'' . addslashes($_POST['db_db']) . '\';'
);

unlink(SCRIPT_ROOT . "install/progress");
file_put_contents(SCRIPT_ROOT . "install/block", '');

json_output("ok", "Instalacja przebiegła pomyślnie. Usuń folder install.", true);
