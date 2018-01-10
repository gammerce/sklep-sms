<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/update/includes/global.php";

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

$queries = SplitSQL(SCRIPT_ROOT . "install/update/queries/" . VERSION . ".sql");
// Wykonujemy zapytania, jedno po drugim
foreach ($queries as $query) {
	if (strlen($query)) {
		try {
			$db->query($query);
		} catch (SqlQueryException $e) {
			$input = array();
			$input[] = "Message: " . $lang->translate('mysqli_' . $e->getMessage());
			$input[] = "Error: " . $e->getError();
			$input[] = "Query: " . $e->getQuery(false);
			file_put_contents(SCRIPT_ROOT . 'errors/update.log', implode("\n", $input));
			file_put_contents(SCRIPT_ROOT . 'install/error', '');
			unlink(SCRIPT_ROOT . "install/progress");
			json_output('error', UPDATE_ERROR, false);
		}
	}
}

unlink(SCRIPT_ROOT . "install/progress");
file_put_contents(SCRIPT_ROOT . "install/block", '');

json_output('ok', "Instalacja przebiegła pomyślnie. Usuń folder install.", true);
