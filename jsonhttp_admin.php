<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "jsonhttp_admin");

require_once "global.php";
require_once SCRIPT_ROOT . "includes/functions_admin_content.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";

// Pobranie akcji
$action = $_POST['action'];

// Send no cache headers
header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$data = array();
if ($action == "charge_wallet") {
	if (!get_privilages("manage_users")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$uid = $_POST['uid'];
	$amount = $_POST['amount'];

	// ID użytkownika
	if ($warning = check_for_warnings("uid", $uid)) {
		$warnings['uid'] = $warning;
	} else {
		$user2 = $heart->get_user($uid);
		if (!isset($user2['uid'])) {
			$warnings['uid'] = "Podane ID użytkownika nie jest przypisane do żadnego konta.<br />";
		}
	}

	// Wartość Doładowania
	if (!$amount) {
		$warnings['amount'] .= "Nie podano wartości doładowania.<br />";
	} else if (!is_numeric($amount)) {
		$warnings['amount'] .= "Wartość doładowania musi być liczbą.<br />";
	}

	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	// Zmiana wartości amount, aby stan konta nie zszedł poniżej zera
	$amount = max($amount, -$user2['wallet']);
	$amount = number_format($amount, 2);

	$service_module = $heart->get_service_module("charge_wallet");
	if (is_null($service_module))
		json_output("wrong_module", "Moduł usługi został źle zaprogramowany.", 0);

	// Dodawanie informacji o płatności do bazy
	$payment_id = pay_by_admin($user);

	// Kupujemy usługę
	$purchase_return = $service_module->purchase(array(
		'user' => array(
			'uid' => $user2['uid'],
			'ip' => $user2['ip'],
			'email' => $user2['email'],
			'name' => $user2['username']
		),
		'transaction' => array(
			'method' => "admin",
			'payment_id' => $payment_id
		),
		'order' => array(
			'amount' => $amount
		)
	));

	log_info("Admin {$user['username']}({$user['uid']}) doładował konto użytkownika: {$user2['username']}({$user2['uid']}) Kwota: {$amount} {$settings['currency']}");

	json_output("charged", "Prawidłowo doładowano konto użytkownika: {$user2['username']} kwotą: {$amount} {$settings['currency']}", 1);
} else if ($action == "add_user_service") {
	if (!get_privilages("manage_player_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Brak usługi
	if ($_POST['service'] == "")
		json_output("no_service", "Nie wybrano usługi.", 0);

	$service_module = $heart->get_service_module($_POST['service']);

	if (is_null($service_module)) {
		json_output("wrong_module", "Moduł usługi został źle zaprogramowany.", 0);
	}

	$return_data = $service_module->admin_add_user_service($_POST);

	if ($return_data === FALSE) {
		json_output("missing_method", "Moduł usługi nie posiada metody dodawania usługi przez admina.", 0);
	}

	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	if ($return_data['status'] == "warnings") {
		foreach ($return_data['data']['warnings'] as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$return_data['data']['warnings'][$brick] = $warning;
		}
	}

	json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
} else if ($action == "edit_user_service") {
	if (!get_privilages("manage_player_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Brak usługi
	if ($_POST['service'] == "")
		json_output("no_service", "Nie wybrano usługi.", 0);

	if (is_null($service_module = $heart->get_service_module($_POST['service'])))
		json_output("wrong_module", "Moduł usługi został źle zaprogramowany.", 0);

	// Sprawdzamy czy dana usługa gracza istnieje
	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Brak takiej usługi w bazie
	if (!$db->num_rows($result))
		json_output("no_service", $lang['no_service'], 0);

	$user_service = $db->fetch_array_assoc($result);

	// Wykonujemy metode edycji usługi gracza przez admina na odpowiednim module
	$return_data = $service_module->admin_edit_user_service($_POST, $user_service);

	if ($return_data === FALSE)
		json_output("missing_method", "Moduł usługi nie posiada metody edycji usługi gracza przez admina.", 0);

	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	if ($return_data['status'] == "warnings") {
		foreach ($return_data['data']['warnings'] as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$return_data['data']['warnings'][$brick] = $warning;
		}
	}

	json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
} else if ($action == "delete_player_service") {
	if (!get_privilages("manage_player_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Pobieramy usługę z bazy
	$player_service = $db->fetch_array_assoc($db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	)));

	// Brak takiej usługi
	if (empty($player_service))
		json_output("no_service", $lang['no_service'], 0);

	// Usunięcie usługi gracza
	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($player_service['id'])
	));
	$affected = $db->affected_rows();

	// Wywolujemy akcje przy usuwaniu
	$service_module = $heart->get_service_module($player_service['service']);
	if ($service_module !== NULL) {
		$service_module->delete_player_service($player_service);
	}

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($affected) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął usługę gracza. ID: {$player_service['id']}");

		json_output("deleted", "Usługa gracza została prawidłowo usunięta.", 1);
	} else
		json_output("not_deleted", "Usługa gracza nie została usunięta.", 0);
} else if ($action == "get_add_user_service_form") {
	if (!get_privilages("manage_player_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$output = "";
	if (($service_module = $heart->get_service_module($_POST['service'])) !== NULL)
		$output = $service_module->get_form("admin_add_user_service");

	output_page($output, "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "add_antispam_question" || $action == "edit_antispam_question") {
	if (!get_privilages("manage_antispam_questions")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Pytanie
	if (!$_POST['question']) {
		$warnings['question'] = "Pole nie może być puste.<br />";
	}

	// Odpowiedzi
	if (!$_POST['answers']) {
		$warnings['answers'] = "Pole nie może być puste.<br />";
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	if ($action == "add_antispam_question") {
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "antispam_questions` ( question, answers ) " .
			"VALUES ('%s','%s')",
			array($_POST['question'], $_POST['answers'])));

		json_output("added", "Pytanie anty-spamowe zostało prawidłowo dodane.", 1);
	} else if ($action == "edit_antispam_question") {
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "antispam_questions` " .
			"SET `question` = '%s', `answers` = '%s' " .
			"WHERE `id` = '%d'",
			array($_POST['question'], $_POST['answers'], $_POST['id'])));

		// Zwróć info o prawidłowej lub błędnej edycji
		if ($db->affected_rows()) {
			log_info("Admin {$user['username']}({$user['uid']}) wyedytował pytanie anty-spamowe. ID: {$_POST['id']}");
			json_output("edited", "Pytanie anty-spamowe zostało prawidłowo wyedytowane.", 1);
		} else
			json_output("not_edited", "Pytanie anty-spamowe nie zostało prawidłowo wyedytowane.", 0);
	}
} else if ($action == "delete_antispam_question") {
	if (!get_privilages("manage_antispam_questions")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "antispam_questions` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął pytanie anty-spamowe. ID: {$_POST['id']}");
		json_output("deleted", "Pytanie anty-spamowe zostało prawidłowo usunięte.", 1);
	} else {
		json_output("not_deleted", "Pytanie anty-spamowe nie zostało usunięte.", 0);
	}
} else if ($action == "edit_settings") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$sms_service = $_POST['sms_service'];
	$transfer_service = $_POST['transfer_service'];
	$currency = $_POST['currency'];
	$shop_url = $_POST['shop_url'];
	$sender_email = $_POST['sender_email'];
	$sender_email_name = $_POST['sender_email_name'];
	$signature = $_POST['signature'];
	$vat = $_POST['vat'];
	$contact = $_POST['contact'];
	$row_limit = $_POST['row_limit'];
	$license_login = $_POST['license_login'];
	$license_password = $_POST['license_password'];
	$cron = $_POST['cron'];
	$language = escape_filename($_POST['language']);
	$theme = escape_filename($_POST['theme']);
	$date_format = $_POST['date_format'];
	$delete_logs = $_POST['delete_logs'];

	// Serwis płatności SMS
	if ($sms_service != '0') {
		$result = $db->query($db->prepare(
			"SELECT id " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' AND sms = '1'",
			array($sms_service)
		));
		if (!$db->num_rows($result)) {
			$warnings['sms_service'] = "Brak serwisu płatności SMS o takim ID.<br />";
		}
	}

	// Serwis płatności internetowej
	if ($transfer_service != '0') {
		$result = $db->query($db->prepare(
			"SELECT id " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' AND transfer = '1'",
			array($transfer_service)
		));
		if (!$db->num_rows($result)) {
			$warnings['transfer_service'] = "Brak serwisu płatności internetowej o takim ID.<br />";
		}
	}

	// Email dla automatu
	if ($warning = check_for_warnings("email", $sender_email)) {
		$warnings['sender_email'] = $warning;
	}

	// VAT
	if ($warning = check_for_warnings("number", $vat)) {
		$warnings['vat'] = $warning;
	}

	// Usuwanie logów
	if ($warning = check_for_warnings("number", $delete_logs)) {
		$warnings['delete_logs'] = $warning;
	}

	// Wierszy na stronę
	if ($warning = check_for_warnings("number", $row_limit)) {
		$warnings['row_limit'] = $warning;
	}

	// Cron
	if (!in_array($cron, array("1", "0"))) {
		$warnings['cron'] = $lang['only_yes_no'];
	}

	// Edytowanie usługi przez gracza
	if (!in_array($_POST['user_edit_service'], array("1", "0"))) {
		$warnings['user_edit_service'] = $lang['only_yes_no'];
	}

	// Motyw
	if (!is_dir(SCRIPT_ROOT . "themes/{$theme}") || $theme[0] == '.')
		$warnings['theme'] = "Podany motyw nie istnieje";

	// Język
	if (!is_dir(SCRIPT_ROOT . "includes/languages/{$language}") || $language[0] == '.')
		$warnings['language'] = "Podany język nie istnieje";

	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	if ($license_password) {
		$set_license_password = $db->prepare("WHEN 'license_password' THEN '%s' ", array(md5($license_password)));
		$key_license_password = ",'license_password'";
	}

	// Edytuj ustawienia
	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "settings` " .
		"SET value = CASE `key` " .
		"WHEN 'sms_service' THEN '%s' " .
		"WHEN 'transfer_service' THEN '%s' " .
		"WHEN 'currency' THEN '%s' " .
		"WHEN 'shop_url' THEN '%s' " .
		"WHEN 'sender_email' THEN '%s' " .
		"WHEN 'sender_email_name' THEN '%s' " .
		"WHEN 'signature' THEN '%s' " .
		"WHEN 'vat' THEN '%.2f' " .
		"WHEN 'contact' THEN '%s' " .
		"WHEN 'row_limit' THEN '%s' " .
		"WHEN 'license_login' THEN '%s' " .
		"WHEN 'cron_each_visit' THEN '%d' " .
		"WHEN 'user_edit_service' THEN '%d' " .
		"WHEN 'theme' THEN '%s' " .
		"WHEN 'language' THEN '%s' " .
		"WHEN 'date_format' THEN '%s' " .
		"WHEN 'delete_logs' THEN '%d' " .
		$set_license_password .
		"END " .
		"WHERE `key` IN ( 'sms_service','transfer_service','currency','shop_url','sender_email','sender_email_name','signature','vat'," .
		"'contact','row_limit','license_login','cron_each_visit','user_edit_service','theme','language','date_format','delete_logs'{$key_license_password} )",
		array($sms_service, $transfer_service, $currency, $shop_url, $sender_email, $sender_email_name, $signature, $vat, $contact, $row_limit,
			$license_login, $cron, $_POST['user_edit_service'], $theme, $language, $date_format, $delete_logs)
	));

	// Zwróć info o prawidłowej lub błędnej edycji
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) wyedytował ustawienia sklepu.");

		json_output("edited", "Ustawienia zostały prawidłowo wyedytowane.", 1);
	} else
		json_output("not_edited", "Nie wyedytowano ustawień.", 0);
} else if ($action == "edit_transaction_service") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Pobieranie danych
	$result = $db->query($db->prepare(
		"SELECT data " .
		"FROM `" . TABLE_PREFIX . "transaction_services` " .
		"WHERE `id` = '%s'",
		array($_POST['id'])
	));
	$transaction_service = $db->fetch_array_assoc($result);
	$transaction_service['data'] = json_decode($transaction_service['data']);
	foreach ($transaction_service['data'] as $key => $value) {
		$arr[$key] = $_POST[$key];
	}

	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "transaction_services` " .
		"SET `data` = '%s' " .
		"WHERE `id` = '%s'",
		array(json_encode($arr), $_POST['id'])));

	// Zwróć info o prawidłowej lub błędnej edycji
	if ($db->affected_rows()) {
		// LOGGING
		log_info("Admin {$user['username']}({$user['uid']}) wyedytował metodę płatności. ID: {$_POST['id']}");

		json_output("edited", "Metoda płatności została prawidłowo wyedytowana.", 1);
	} else
		json_output("not_edited", "Nie udało się wyedytować metody płatności.", 0);
} else if ($action == "add_service" || $action == "edit_service") {
	if (!get_privilages("manage_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// ID
	if (!strlen($_POST['id'])) { // Nie podano id usługi
		$warnings['id'] = "Nie wprowadzono ID usługi.<br />";
	} else if ($action == "add_service") {
		if (strlen($_POST['id']) > 16)
			$warnings['id'] = "Wprowadzone ID usługi jest zbyt długie. Maksymalnie 16 znaków.<br />";
	}

	if (($action == "add_service" && !isset($warnings['id'])) || ($action == "edit_service" && $_POST['id'] !== $_POST['id2']))
		// Sprawdzanie czy usługa o takim ID już istnieje
		if ($heart->get_service($_POST['id']) !== NULL)
			$warnings['id'] = "Usługa o takim ID już istnieje.<br />";

	// Nazwa
	if (!strlen($_POST['name'])) {
		$warnings['name'] = "Nie wprowadzono nazwy usługi.<br />";
	}

	// Opis
	if ($warning = check_for_warnings("service_description", $_POST['short_description']))
		$warnings['short_description'] = $warning;

	// Kolejność
	if ($_POST['order'] != intval($_POST['order'])) {
		$warnings['order'] = "Pole musi być liczbą całkowitą.<br />";
	}

	// Grupy
	foreach ($_POST['groups'] as $group) {
		if (is_null($heart->get_group($group))) {
			$warnings['groups[]'] .= "Wybrano błędną grupę.<br />";
			break;
		}
	}

	// Moduł usługi
	if ($action == "add_service") {
		if (($service_module = $heart->get_service_module_s($_POST['module'])) === NULL)
			$warnings['module'] = "Wybrano nieprawidłowy moduł.<br />";
	}
	else
		$service_module = $heart->get_service_module($_POST['id2']); // TODO Zmienic na get_service_module_empty

	// Przed błędami
	if ($service_module !== NULL) {
		$additional_warnings = $service_module->manage_service_pre($_POST);
		$warnings = array_merge((array)$warnings, (array)$additional_warnings);
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	// Po błędach wywołujemy na metodę modułu
	if ($service_module !== NULL)
		$module_data = $service_module->manage_service_post($_POST);

	if ($action == "add_service") {
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "services` " .
			"SET `id`='%s', `name`='%s', `short_description`='%s', `description`='%s', `tag`='%s', " .
			"`module`='%s', `groups`='%s', `order` = '%d'{$module_data['query_set']}",
			array($_POST['id'], $_POST['name'], $_POST['short_description'], $_POST['description'], $_POST['tag'], $_POST['module'],
				implode(";", $_POST['groups']), $_POST['order'])
		));

		log_info("Admin {$user['username']}({$user['uid']}) dodał usługę. ID: {$_POST['id']}");
		json_output("added", $lang['service_added'], 1, array('length' => 10000));
	} else if ($action == "edit_service") {
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "services` " .
			"SET `id` = '%s', `name` = '%s', `short_description` = '%s', `description` = '%s', " .
			"`tag` = '%s', `groups` = '%s', `order` = '%d' " . $module_data['query_set'] .
			"WHERE `id` = '%s'",
			array($_POST['id'], $_POST['name'], $_POST['short_description'], $_POST['description'], $_POST['tag'],  implode(";", $_POST['groups']),
				$_POST['order'], $_POST['id2'])
		));

		// Zwróć info o prawidłowej lub błędnej edycji
		if ($db->affected_rows()) {
			log_info("Admin {$user['username']}({$user['uid']}) wyedytował usługę. ID: {$_POST['id2']}");
			json_output("edited", "Usługa została prawidłowo wyedytowana.", 1);
		} else
			json_output("not_edited", "Usługa nie została wyedytowana.", 0);
	}
} else if ($action == "delete_service") {
	if (!get_privilages("manage_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Wywolujemy akcje przy uninstalacji
	$service_module = $heart->get_service_module($_POST['id']);
	if (!is_null($service_module)) {
		$service_module->delete_service($_POST['id']);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "pricelist` " .
		"WHERE `service` = '%s'",
		array($_POST['id'])
	));

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "services` " .
		"WHERE `id` = '%s'",
		array($_POST['id'])
	));
	$affected = $db->affected_rows();

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($affected) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął usługę. ID: {$_POST['id']}");
		json_output("deleted", "Usługa została prawidłowo usunięta.", 1);
	} else json_output("not_deleted", "Usługa nie została usunięta.", 0);
} else if ($action == "get_service_module_extra_fields") {
	if (!get_privilages("manage_player_services")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$output = "";
	// Pobieramy moduł obecnie edytowanej usługi, jeżeli powróciliśmy do pierwotnego modułu
	// W przeciwnym razie pobieramy wybrany moduł
	if (is_null($service_module = $heart->get_service_module($_POST['service'])) || $service_module::MODULE_ID != $_POST['module'])
		$service_module = $heart->get_service_module_s($_POST['module']);

	if (!is_null($service_module))
		$output = $service_module->service_extra_fields();

	output_page($output, "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "add_server" || $action == "edit_server") {
	if (!get_privilages("manage_servers")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Nazwa
	if (!$_POST['name']) { // Nie podano nazwy serwera
		$warnings['name'] = "Pole nie może być puste.<br />";
	}

	// IP
	if (!$_POST['ip']) { // Nie podano nazwy serwera
		$warnings['ip'] = "Pole nie może być puste.<br />";
	}
	$_POST['ip'] = trim($_POST['ip']);

	// Port
	if (!$_POST['port']) { // Nie podano nazwy serwera
		$warnings['port'] = "Pole nie może być puste.<br />";
	}
	$_POST['port'] = trim($_POST['port']);

	// Serwis płatności SMS
	if ($_POST['sms_service']) {
		$result = $db->query($db->prepare(
			"SELECT id " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' AND sms = '1'",
			array($_POST['sms_service'])
		));
		if (!$db->num_rows($result)) {
			$warnings['sms_service'] = "Brak serwisu płatności SMS o takim ID.<br />";
		}
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	$set = "";
	foreach ($heart->get_services() as $service) {
		// Dana usługa nie może być kupiona na serwerze
		if (!is_null($service_module = $heart->get_service_module($service['id'])) && !$service_module->info['available_on_servers'])
			continue;

		$set .= $db->prepare(", `%s`='%d'", array($service['id'], $_POST[$service['id']]));
	}

	if ($action == "add_server") {
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "servers` " .
			"SET `name`='%s', `ip`='%s', `port`='%s', `sms_service`='%s'{$set}",
			array($_POST['name'], $_POST['ip'], $_POST['port'], $_POST['sms_service'])));

		log_info("Admin {$user['username']}({$user['uid']}) dodał serwer. ID: " . $db->last_id());
		// Zwróć info o prawidłowym zakończeniu dodawania
		json_output("added", "Serwer został prawidłowo dodany.", 1);
	} else if ($action == "edit_server") {
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "servers` " .
			"SET `name` = '%s', `ip` = '%s', `port` = '%s', `sms_service` = '%s'{$set} " .
			"WHERE `id` = '%s'",
			array($_POST['name'], $_POST['ip'], $_POST['port'], $_POST['sms_service'], $_POST['id'])
		));

		// Zwróć info o prawidłowej lub błędnej edycji
		if ($db->affected_rows()) {
			// LOGGING
			log_info("Admin {$user['username']}({$user['uid']}) wyedytował serwer. ID: {$_POST['id']}");
			json_output("edited", "Serwer został prawidłowo wyedytowany.", 1);
		} else
			json_output("not_edited", "Serwer nie został prawidłowo wyedytowany.", 0);
	}
} else if ($action == "delete_server") {
	if (!get_privilages("manage_servers")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "servers` " .
		"WHERE `id` = '%s'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął serwer. ID: {$_POST['id']}");
		json_output("deleted", "Serwer został prawidłowo usunięty.", 1);
	} else json_output("not_deleted", "Serwer nie został usunięty.", 0);
} else if ($action == "edit_user") {
	if (!get_privilages("manage_users")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$user2 = $heart->get_user($_POST['uid']);

	// Nazwa użytkownika
	if ($user2['username'] != $_POST['username']) {
		if ($warning = check_for_warnings("username", $_POST['username']))
			$warnings['username'] = $warning;
		$result = $db->query($db->prepare(
			"SELECT `uid` " .
			"FROM `" . TABLE_PREFIX . "users` " .
			"WHERE username = '%s'",
			array($_POST['username'])
		));
		if ($db->num_rows($result)) {
			$warnings['username'] .= "Podana nazwa użytkownika jest już zajęta.<br />";
		}
	}

	// E-mail
	if ($user2['email'] != $_POST['email']) {
		if ($warning = check_for_warnings("email", $_POST['email']))
			$warnings['email'] = $warning;
		$result = $db->query($db->prepare(
			"SELECT `uid` " .
			"FROM `" . TABLE_PREFIX . "users` " .
			"WHERE email = '%s'",
			array($_POST['email'])
		));
		if ($db->num_rows($result)) {
			$warnings['email'] .= "Podany e-mail jest już zajęty.<br />";
		}
	}

	// Grupy
	foreach ($_POST['groups'] as $gid) {
		if (is_null($heart->get_group($gid))) {
			$warnings['groups[]'] .= "Wybrano błędną grupę.<br />";
			break;
		}
	}

	// Portfel
	if ($warning = check_for_warnings("number", $_POST['wallet']))
		$warnings['wallet'] = $warning;

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET `username` = '%s', `forename` = '%s', `surname` = '%s', `email` = '%s', `groups` = '%s', `wallet` = '%f' " .
		"WHERE `uid` = '%d'",
		array($_POST['username'], $_POST['forename'], $_POST['surname'], $_POST['email'], implode(";", $_POST['groups']),
			number_format($_POST['wallet'], 2), $_POST['uid'])
	));

	// Zwróć info o prawidłowej lub błędnej edycji
	if ($db->affected_rows()) {
		// LOGGING
		log_info("Admin {$user['username']}({$user['uid']}) wyedytował użytkownika. ID: {$_POST['uid']}");
		json_output("edited", "Użytkownik został prawidłowo wyedytowany.", 1);
	} else
		json_output("not_edited", "Użytkownik nie został prawidłowo wyedytowany.", 0);
} else if ($action == "delete_user") {
	if (!get_privilages("manage_users")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "users` " .
		"WHERE `uid` = '%d'",
		array($_POST['uid'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął użytkownika. ID: {$_POST['uid']}");
		json_output("deleted", "Użytkownik został prawidłowo usunięty.", 1);
	} else json_output("not_deleted", "Użytkownik nie został usunięty.", 0);
} else if ($action == "add_group" || $action == "edit_group") {
	if (!get_privilages("manage_groups")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$set = "";
	$result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
	while ($row = $db->fetch_array_assoc($result)) {
		if (in_array($row['Field'], array("id", "name"))) continue;

		$set .= $db->prepare(", `%s`='%d'", array($row['Field'], $_POST[$row['Field']]));
	}

	if ($action == "add_group") {
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "groups` " .
			"SET `name` = '%s'{$set}",
			array($_POST['name'])
		));

		log_info("Admin {$user['username']}({$user['uid']}) dodał grupę. ID: " . $db->last_id());
		// Zwróć info o prawidłowym zakończeniu dodawania
		json_output("added", "Grupa została prawidłowo dodana.", 1);
	} else if ($action == "edit_group") {
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "groups` " .
			"SET `name` = '%s'{$set} " .
			"WHERE `id` = '%d'",
			array($_POST['name'], $_POST['id'])
		));

		// Zwróć info o prawidłowej lub błędnej edycji
		if ($db->affected_rows()) {
			// LOGGING
			log_info("Admin {$user['username']}({$user['uid']}) wyedytował grupę. ID: {$_POST['id']}");
			json_output("edited", "Grupa została prawidłowo wyedytowana.", 1);
		} else
			json_output("not_edited", "Grupa nie została prawidłowo wyedytowana.", 0);
	}
} else if ($action == "delete_group") {
	if (!get_privilages("manage_groups")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "groups` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął grupę. ID: {$_POST['id']}");
		json_output("deleted", "Grupa została prawidłowo usunięta.", 1);
	} else json_output("not_deleted", "Grupa nie została usunięta.", 0);
} else if ($action == "add_tariff") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Taryfa
	if ($warning = check_for_warnings("number", $_POST['tariff'])) {
		$warnings['tariff'] = $warning;
	}
	if (($heart->get_tariff($_POST['tariff'])) !== NULL) {
		$warnings['tariff'] .= "Taka taryfa już istnieje.<br />";
	}

	// Prowizja
	if ($warning = check_for_warnings("number", $_POST['provision'])) {
		$warnings['provision'] = $warning;
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	$db->query($db->prepare(
		"INSERT " .
		"INTO " . TABLE_PREFIX . "tariffs (tariff,provision) " .
		"VALUES( '%d', '%.2f' )",
		array($_POST['tariff'], $_POST['provision'])
	));

	log_info("Admin {$user['username']}({$user['uid']}) dodał taryfę. ID: " . $db->last_id());
	// Zwróć info o prawidłowym dodaniu
	json_output("added", "Taryfa została prawidłowo dodana.", 1);
} else if ($action == "edit_tariff") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Prowizja
	if ($warning = check_for_warnings("number", $_POST['provision'])) {
		$warnings['provision'] = $warning;
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "tariffs` " .
		"SET `provision` = '%.2f' " .
		"WHERE `tariff` = '%d'",
		array($_POST['provision'], $_POST['tariff'])));

	// Zwróć info o prawidłowej lub błędnej edycji
	if ($affected || $db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) wyedytował taryfę. ID: {$_POST['id']}");
		json_output("edited", "Taryfa została prawidłowo wyedytowana.", 1);
	} else json_output("not_edited", "Taryfa nie została wyedytowana.", 0);
} else if ($action == "delete_tariff") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "tariffs` " .
		"WHERE `tariff` = '%d' AND `predefined` = '%d'",
		array($_POST['tariff'], 0)
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął taryfę. ID: {$_POST['tariff']}");
		json_output("deleted", "Taryfa została prawidłowo usunięta.", 1);
	} else {
		json_output("not_deleted", "Taryfa nie została usunięta.", 0);
	}
} else if ($action == "add_price" || $action == "edit_price") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Usługa
	if (is_null($heart->get_service($_POST['service']))) {
		$warnings['service'] .= "Taka usługa nie istnieje.<br />";
	}

	// Serwer
	if ($_POST['server'] != -1 && is_null($heart->get_server($_POST['server']))) {
		$warnings['server'] .= "Taki serwer nie istnieje.<br />";
	}

	// Taryfa
	if (($heart->get_tariff($_POST['tariff'])) === NULL) {
		$warnings['tariff'] .= "Taka taryfa nie istnieje.<br />";
	}

	// Ilość
	if ($warning = check_for_warnings("number", $_POST['amount'])) {
		$warnings['amount'] = $warning;
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	if ($action == "add_price") {
		$db->query($db->prepare(
			"INSERT " .
			"INTO " . TABLE_PREFIX . "pricelist (service,tariff,amount,server) " .
			"VALUES( '%s', '%d', '%d', '%d' )",
			array($_POST['service'], $_POST['tariff'], $_POST['amount'], $_POST['server'])));

		log_info("Admin {$user['username']}({$user['uid']}) dodał cenę. ID: " . $db->last_id());

		// Zwróć info o prawidłowym dodaniu
		json_output("added", "Cena została prawidłowo dodana.", 1);
	} else if ($action == "edit_price") {
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "pricelist` " .
			"SET `service` = '%s', `tariff` = '%d', `amount` = '%d', `server` = '%d' " .
			"WHERE `id` = '%d'",
			array($_POST['service'], $_POST['tariff'], $_POST['amount'], $_POST['server'], $_POST['id'])));

		// Zwróć info o prawidłowej lub błędnej edycji
		if ($db->affected_rows()) {
			log_info("Admin {$user['username']}({$user['uid']}) wyedytował cenę. ID: {$_POST['id']}");
			json_output("edited", "Cena została prawidłowo wyedytowana.", 1);
		} else json_output("not_edited", "Cena nie została wyedytowana.", 0);
	}
} else if ($action == "delete_price") {
	if (!get_privilages("manage_settings")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "pricelist` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął cenę. ID: {$_POST['id']}");
		json_output("deleted", "Cena została prawidłowo usunięta.", 1);
	} else json_output("not_deleted", "Cena nie została usunięta.", 0);
} else if ($action == "add_sms_code") {
	if (!get_privilages("manage_sms_codes")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	// Taryfa
	if ($warning = check_for_warnings("number", $_POST['tariff'])) {
		$warnings['tariff'] = $warning;
	}

	// Kod SMS
	if ($warning = check_for_warnings("sms_code", $_POST['code'])) {
		$warnings['code'] = $warning;
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			eval("\$warning = \"" . get_template("form_warning") . "\";");
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang['form_wrong_filled'], 0, $data);
	}

	$db->query($db->prepare(
		"INSERT " .
		"INTO " . TABLE_PREFIX . "sms_codes (code,tariff) " .
		"VALUES( '%s', '%d' )",
		array(strtoupper($_POST['code']), $_POST['tariff'])));

	log_info("Admin {$user['username']}({$user['uid']}) dodał kod SMS. Kod: {$_POST['code']}, Taryfa: {$_POST['tariff']}");
	// Zwróć info o prawidłowym dodaniu
	json_output("added", "Kod SMS został prawidłowo dodany.", 1);
} else if ($action == "delete_sms_code") {
	if (!get_privilages("manage_sms_codes")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$result = $db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "sms_codes` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunięciu
	if ($db->affected_rows()) {
		log_info("Admin {$user['username']}({$user['uid']}) usunął kod SMS. ID: {$_POST['id']}");
		json_output("deleted", "Kod SMS został prawidłowo usunięty.", 1);
	} else json_output("not_deleted", "Kod SMS nie został usunięty.", 0);
} else if ($action == "delete_log") {
	if (!get_privilages("manage_logs")) {
		json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
	}

	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "logs` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Zwróć info o prawidłowym lub błędnym usunieciu
	if ($db->affected_rows()) json_output("deleted", "Log został prawidłowo usunięty.", 1);
	else json_output("not_deleted", "Log nie został usunięty.", 0);
} else if ($action == "refresh_bricks") {
	if (isset($_POST['bricks']))
		$bricks = explode(";", $_POST['bricks']);

	foreach ($bricks as $brick) {
		$array = get_content($brick, false, true);
		$data[$brick]['class'] = $array['class'];
		$data[$brick]['content'] = $array['content'];
	}

	output_page(json_encode($data), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "get_template") {
	$template = $_POST['template'];
	// Zabezpieczanie wszystkich wartości post
	foreach ($_POST as $key => $value) {
		$_POST[$key] = htmlspecialchars($value);
	}

	if ($template == "admin_charge_wallet") {
		if (!get_privilages("manage_users")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		$username = htmlspecialchars($_POST['username']);
		$uid = htmlspecialchars($_POST['uid']);
	} else if ($template == "admin_user_wallet") {
		if (!get_privilages("manage_users")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		$user2 = $heart->get_user($_POST['uid']);
	} else if ($template == "admin_add_user_service") {
		if (!get_privilages("manage_player_services")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		// Pobranie usług
		foreach ($heart->get_services() as $id => $row) {
			if (($service_module = $heart->get_service_module($id)) === NULL
				|| !class_has_interface($service_module, "IServiceAdminManageUserService")
			)
				continue;

			$services .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}
	} else if ($template == "admin_edit_user_service") {
		if (!get_privilages("manage_player_services")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		// Pobieramy usługę z bazy
		$player_service = $db->fetch_array_assoc($db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `id` = '%d'",
			array($_POST['id'])
		)));

		if (($service_module = $heart->get_service_module($player_service['service'])) !== NULL) {
			$service_module_id = htmlspecialchars($service_module::MODULE_ID);
			$form_data = $service_module->get_form("admin_edit_user_service", $player_service);
		}

		if (!isset($form_data) || $form_data === "")
			$form_data = "Tej usługi nie da rady edytować.";
	} else if ($template == "admin_edit_transaction_service") {
		if (!get_privilages("manage_settings")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		// Pobranie danych o metodzie płatności
		$result = $db->query($db->prepare(
			"SELECT * " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s'",
			array($_POST['id'])
		));
		$transaction_service = $db->fetch_array_assoc($result);

		$transaction_service['id'] = htmlspecialchars($transaction_service['id']);
		$transaction_service['name'] = htmlspecialchars($transaction_service['name']);
		$transaction_service['data'] = json_decode($transaction_service['data']);
		foreach ($transaction_service['data'] as $name => $value) {
			switch ($name) {
				case 'sms_text':
					$text = "KOD SMS";
					break;
				case 'account_id':
					$text = "ID KONTA";
					break;
				default:
					$text = strtoupper($name);
					break;
			}
			eval("\$data_values .= \"" . get_template("tr_name_input") . "\";");
		}
	} else if ($template == "admin_edit_tariff") {
		if (!get_privilages("manage_settings")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		$tariff = htmlspecialchars($_POST['tariff']);
		$provision = number_format($heart->get_tariff_provision($_POST['tariff']), 2);
	} else if ($template == "admin_add_price" || $template == "admin_edit_price") {
		if (!get_privilages("manage_settings")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		if ($template == "admin_edit_price") {
			$result = $db->query($db->prepare(
				"SELECT * " .
				"FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `id` = '%d'",
				array($_POST['id'])
			));
			$price = $db->fetch_array_assoc($result);

			$all_servers = $price['server'] == -1 ? "selected" : "";
		}

		// Pobranie Usług
		foreach ($heart->get_services() as $service_id => $service) {
			$services .= create_dom_element("option", $service['name'] . " ( " . $service['id'] . " )", array(
				'value' => $service['id'],
				'selected' => isset($price) && $price['service'] == $service['id'] ? "selected" : ""
			));
		}

		// Pobranie serwerów
		foreach ($heart->get_servers() as $server_id => $server) {
			$servers .= create_dom_element("option", $server['name'], array(
				'value' => $server['id'],
				'selected' => isset($price) && $price['server'] == $server['id'] ? "selected" : ""
			));
		}

		// Pobranie Taryf
		foreach ($heart->get_tariffs() as $tariff_data) {
			$tariffs .= create_dom_element("option", $tariff_data['tariff'], array(
				'value' => $tariff_data['tariff'],
				'selected' => isset($price) && $price['tariff'] == $tariff_data['tariff'] ? "selected" : ""
			));
		}
	} else if ($template == "admin_add_sms_code") {
		if (!get_privilages("manage_sms_codes")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		foreach ($heart->get_tariffs() as $tariff_data) {
			$tariffs .= create_dom_element("option", $tariff_data['tariff'], array(
				'value' => $tariff_data['tariff']
			));
		}
	} else if ($template == "admin_add_service" || $template == "admin_edit_service") {
		if (!get_privilages("manage_services")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		if ($template == "admin_edit_service") {
			$service = $heart->get_service($_POST['id']);
			$service['tag'] = htmlspecialchars($service['tag']);

			// Pobieramy pola danego modułu
			if ($service['module'])
				if (($service_module = $heart->get_service_module($service['id'])) !== NULL) {
					$extra_fields = create_dom_element("tbody", $service_module->service_extra_fields(), array(
						'class' => 'extra_fields'
					));
				}
		}

		// Pobranie dostępnych modułów usług
		if ($template == "admin_add_service") {
			$services_modules = "";
			foreach ($heart->get_services_modules() as $module) {
				// Sprawdzamy czy dany moduł zezwala na tworzenie nowych usług, które będzie obsługiwał
				if (is_null($service_module = $heart->get_service_module_s($module['id'])) || !class_has_interface($service_module, "IServiceCreateNew"))
					continue;

				$services_modules .= create_dom_element("option", $module['name'], array(
					'value' => $module['id'],
					'selected' => isset($service['module']) && $service['module'] == $module['id'] ? "selected" : ""
				));
			}
		}
		else
			$service_module = $heart->get_service_module_name($service['module']);

		// Grupy
		$groups = "";
		foreach ($heart->get_groups() as $group) {
			$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
				'value' => $group['id'],
				'selected' => isset($service['groups']) && in_array($group['id'], $service['groups']) ? "selected" : ""
			));
		}
	} else if ($template == "admin_add_server" || $template == "admin_edit_server") {
		if (!get_privilages("manage_servers")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		if ($template == "admin_edit_server") {
			$server = $heart->get_server($_POST['id']);
			$server['ip'] = htmlspecialchars($server['ip']);
			$server['port'] = htmlspecialchars($server['port']);
		}

		// Pobranie listy serwisów transakcyjnych
		$result = $db->query(
			"SELECT id, name, sms " .
			"FROM `" . TABLE_PREFIX . "transaction_services`"
		);
		while ($row = $db->fetch_array_assoc($result)) {
			if (!$row['sms'])
				continue;

			$sms_services .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $row['id'] == $server['sms_service'] ? "selected" : ""
			));
		}


		foreach ($heart->get_services() as $service) {
			// Dana usługa nie może być kupiona na serwerze
			if (!is_null($service_module = $heart->get_service_module($service['id'])) && !$service_module->info['available_on_servers'])
				continue;

			$values = create_dom_element("option", "NIE", array(
				'value' => 0,
				'selected' => $server[$service['id']] ? "" : "selected"
			));

			$values .= create_dom_element("option", "TAK", array(
				'value' => 1,
				'selected' => $server[$service['id']] ? "selected" : ""
			));

			$name = htmlspecialchars($service['id']);
			$text = htmlspecialchars("{$service['name']} ( {$service['id']} )");

			eval("\$services .= \"" . get_template("tr_text_select") . "\";");
		}
	} else if ($template == "admin_edit_user") {
		if (!get_privilages("manage_users")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		// Pobranie użytkownika
		$row = $heart->get_user($_POST['uid']);

		$groups = "";
		foreach ($heart->get_groups() as $group) {
			$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
				'value' => $group['id'],
				'selected' => in_array($group['id'], $row['groups']) ? "selected" : ""
			));
		}
	} else if ($template == "admin_add_group" || $template == "admin_edit_group") {
		if (!get_privilages("manage_groups")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		if ($template == "admin_edit_group") {
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "groups` " .
				"WHERE `id` = '%d'",
				array($_POST['id'])
			));

			if (!$db->num_rows($result)) {
				$data['template'] = create_dom_element("form", $lang['no_such_group'], array(
					'class' => 'action_box',
					'style' => array(
						'padding' => "20px",
						'color' => "white"
					)
				));
			} else {
				$group = $db->fetch_array_assoc($result);
				$group['name'] = htmlspecialchars($group['name']);
			}
		}

		$result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
		while ($row = $db->fetch_array_assoc($result)) {
			if (in_array($row['Field'], array("id", "name"))) continue;

			$values = create_dom_element("option", "NIE", array(
				'value' => 0,
				'selected' => $group[$row['Field']] ? "" : "selected"
			));

			$values .= create_dom_element("option", "TAK", array(
				'value' => 1,
				'selected' => $group[$row['Field']] ? "selected" : ""
			));

			$name = htmlspecialchars($row['Field']);
			$text = $lang['privilages_names'][$row['Field']];

			eval("\$privilages .= \"" . get_template("tr_text_select") . "\";");
		}
	} else if ($template == "admin_edit_antispam_question") {
		if (!get_privilages("manage_antispam_questions")) {
			json_output("not_logged_in", $lang['not_logged_or_no_perm'], 0);
		}

		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"WHERE `id` = '%d'",
			array($_POST['id'])
		));
		$row = $db->fetch_array_assoc($result);
		$row['question'] = htmlspecialchars($row['question']);
		$row['answers'] = htmlspecialchars($row['answers']);
	}

	if (!isset($data['template']))
		eval("\$data['template'] = \"" . get_template("jsonhttp/" . $template) . "\";");

	output_page(json_encode($data), "Content-type: text/plain; charset=\"UTF-8\"");
}

json_output("script_error", "Błąd programistyczny.", 0);