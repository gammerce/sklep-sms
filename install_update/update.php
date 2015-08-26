<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install_update");

require_once "global.php";

$db = new Database($db_host, $db_user, $db_pass, $db_name);

$everything_ok = true;
$update_info = update_info($everything_ok);

// Nie wszystko jest git
if (!$everything_ok) {
	json_output("warnings", "Aktualizacja nie mogła zostać przeprowadzona. Nie wszystkie warunki są spełnione.", false, array(
		'update_info' => $update_info
	));
}

// -------------------- INSTALACJA --------------------

file_put_contents(SCRIPT_ROOT . "install/progress", "");

$db->query("SET NAMES utf8");

$queries = SplitSQL(SCRIPT_ROOT . "install/queries.sql");//explode("\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/queries.sql")));
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
			file_put_contents(SCRIPT_ROOT . 'errors/update.log', implode("\n", $input));
			file_put_contents(SCRIPT_ROOT . 'install/error', '');
			unlink(SCRIPT_ROOT . "install/progress");
			json_output('error', UPDATE_ERROR, false);
		}
	}
}

// ---------------- VERSION 3.3.2 ---------------------
try {
	$db->query("ALTER TABLE `ss_groups` CHANGE `view_player_services` `view_user_services` TINYINT(1) NOT NULL DEFAULT '0';");
	$db->query("ALTER TABLE `ss_groups` CHANGE `manage_player_services` `manage_user_services` TINYINT(1) NOT NULL DEFAULT '0';");
} catch (SqlQueryException $e) {
}
// -------------- END VERSION 3.3.2 -------------------

unlink(SCRIPT_ROOT . "install/progress");
file_put_contents(SCRIPT_ROOT . "install/block", '');

json_output('ok', "Instalacja przebiegła pomyślnie. Usuń folder install.", true);
