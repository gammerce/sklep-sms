<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "jsonhttp");

require_once "global.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";

// Pobranie akcji
$action = $_POST['action'];

// Send no cache headers
header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$data = array();
if ($action == "login") {
	if (is_logged())
		json_output("already_logged_in");

	if (!$_POST['username'] || !$_POST['password'])
		json_output("no_data", $lang->no_login_password, 0);

	$user = $heart->get_user(0, $_POST['username'], $_POST['password']);
	if (is_logged()) {
		$_SESSION['uid'] = $user['uid'];
		update_activity($user['uid']);
		json_output("logged_in", $lang->login_success, 1);
	}

	json_output("not_logged", $lang->bad_pass_nick, 0);
} else if ($action == "logout") {
	if (!is_logged())
		json_output("already_logged_out");

	// Unset all of the session variables.
	$_SESSION = array();

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}

	// Finally, destroy the session.
	session_destroy();

	json_output("logged_out", $lang->logout_success, 1);
} else if ($action == "set_session_language") {
	setcookie("language", escape_filename($_POST['language']), time() + (86400 * 30), "/"); // 86400 = 1 day
	exit;
} else if ($action == "register") {
	if (is_logged())
		json_output("logged_in", $lang->logged, 0);

	$username = trim($_POST['username']);
	$password = $_POST['password'];
	$passwordr = $_POST['password_repeat'];
	$email = trim($_POST['email']);
	$emailr = trim($_POST['email_repeat']);
	$forename = trim($_POST['forename']);
	$surname = trim($_POST['surname']);
	$as_id = $_POST['as_id'];
	$as_answer = $_POST['as_answer'];

	// Pobranie nowego pytania antyspamowego
	$antispam_question = $db->fetch_array_assoc($db->query(
		"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
		"ORDER BY RAND() " .
		"LIMIT 1"
	));
	$data['antispam']['question'] = $antispam_question['question'];
	$data['antispam']['id'] = $antispam_question['id'];

	// Sprawdzanie czy podane id pytania antyspamowego jest prawidlowe
	if (!isset($_SESSION['asid']) || $as_id != $_SESSION['asid'])
		json_output("wrong_sign", $lang->wrong_sign, 0, $data);

	// Zapisujemy id pytania antyspamowego
	$_SESSION['asid'] = $antispam_question['id'];

	// Nazwa użytkownika
	if ($warning = check_for_warnings("username", $username))
		$warnings['username'] = $warning;

	$result = $db->query($db->prepare(
		"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
		"WHERE `username` = '%s'",
		array($username)
	));
	if ($db->num_rows($result))
		$warnings['username'] .= $lang->nick_occupied . "<br />";

	// Hasło
	if ($warning = check_for_warnings("password", $password))
		$warnings['password'] = $warning;
	if ($password != $passwordr)
		$warnings['password_repeat'] .= $lang->different_pass . "<br />";

	if ($warning = check_for_warnings("email", $email))
		$warnings['email'] = $warning;

	// Email
	$result = $db->query($db->prepare(
		"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
		"WHERE `email` = '%s'",
		array($email)
	));
	if ($db->num_rows($result))
		$warnings['email'] .= $lang->email_occupied . "<br />";

	if ($email != $emailr)
		$warnings['email_repeat'] .= $lang->different_email . "<br />";

	// Pobranie z bazy pytania antyspamowego
	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
		"WHERE `id` = '%d'",
		array($as_id)
	));
	$antispam_question = $db->fetch_array_assoc($result);
	if (!in_array(strtolower($as_answer), explode(";", $antispam_question['answers'])))
		$warnings['as_answer'] .= $lang->wrong_anti_answer . "<br />";

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang->form_wrong_filled, 0, $data);
	}

	$salt = get_random_string(8);
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`) " .
		"VALUES ('%s','%s','%s','%s','%s','%s','%s')",
		array($username, hash_password($password, $salt), $salt, $email, $forename, $surname, $user['ip'])
	));

	// LOGING
	log_info($lang_shop->sprintf($lang_shop->new_account, $db->last_id(), $username, $user['ip']));

	json_output("registered", $lang->register_success, 1, $data);
} else if ($action == "forgotten_password") {
	if (is_logged())
		json_output("logged_in", $lang->logged, 0);

	$username = trim($_POST['username']);
	$email = trim($_POST['email']);

	if ($username || (!$username && !$email)) {
		if ($warning = check_for_warnings("username", $username))
			$warnings['username'] = $warning;
		if (strlen($username)) {
			$result = $db->query($db->prepare(
				"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
				"WHERE `username` = '%s'",
				array($username)
			));

			if (!$db->num_rows($result))
				$warnings['username'] .= $lang->nick_no_account . "<br />";
			else
				$row = $db->fetch_array_assoc($result);
		}
	}

	if (!strlen($username)) {
		if ($warning = check_for_warnings("email", $email))
			$warnings['email'] = $warning;
		if (strlen($email)) {
			$result = $db->query($db->prepare(
				"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
				"WHERE `email` = '%s'",
				array($email)
			));

			if (!$db->num_rows($result))
				$warnings['email'] .= $lang->email_no_account . "<br />";
			else
				$row = $db->fetch_array_assoc($result);
		}
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang->form_wrong_filled, 0, $data);
	}

	// Pobranie danych użytkownika
	$user2 = $heart->get_user($row['uid']);

	$key = get_random_string(32);
	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET `reset_password_key`='%s' " .
		"WHERE `uid`='%d'",
		array($key, $user2['uid'])
	));

	$link = $settings['shop_url'] . "/index.php?pid=reset_password&code=" . htmlspecialchars($key);
	eval("\$text = \"" . get_template("emails/forgotten_password") . "\";");
	$ret = send_email($user2['email'], $user2['username'], "Reset Hasła", $text);

	if ($ret == "not_sent")
		json_output("not_sent", $lang->keyreset_error, 0);
	else if ($ret == "wrong_email")
		json_output("wrong_sender_email", $lang->wrong_email, 0);
	else if ($ret == "sent") {
		log_info($lang_shop->sprintf($lang_shop->reset_key_email, $user2['username'], $user2['uid'], $user2['email'], $username, $email));
		$data['username'] = $user2['username'];
		json_output("sent", $lang->email_sent, 1, $data);
	}
} else if ($action == "reset_password") {
	if (is_logged())
		json_output("logged_in", $lang->logged, 0);

	$uid = $_POST['uid'];
	$sign = $_POST['sign'];
	$pass = $_POST['pass'];
	$passr = $_POST['pass_repeat'];

	// Sprawdzanie hashu najwazniejszych danych
	if (!$sign || $sign != md5($uid . $settings['random_key']))
		json_output("wrong_sign", $lang->wrong_sign, 0);

	if ($warning = check_for_warnings("password", $pass))
		$warnings['pass'] = $warning;
	if ($pass != $passr)
		$warnings['pass_repeat'] .= $lang->different_pass;

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang->form_wrong_filled, 0, $data);
	}

	// Zmień hasło
	$salt = get_random_string(8);

	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET `password` = '%s', `salt` = '%s', `reset_password_key` = '' " .
		"WHERE `uid` = '%d'",
		array(hash_password($pass, $salt), $salt, $uid)
	));

	// LOGING
	log_info($lang_shop->sprintf($lang_shop->reset_pass, $uid));

	json_output("password_changed", $lang->password_changed, 1);
} else if ($action == "change_password") {
	if (!is_logged())
		json_output("logged_in", $lang->not_logged, 0);

	$oldpass = $_POST['old_pass'];
	$pass = $_POST['pass'];
	$passr = $_POST['pass_repeat'];

	if ($warning = check_for_warnings("password", $pass))
		$warnings['pass'] = $warning;
	if ($pass != $passr) {
		$warnings['pass_repeat'] .= $lang->different_pass;
	}

	if (hash_password($oldpass, $user['salt']) != $user['password']) {
		$warnings['old_pass'] .= $lang->old_pass_wrong . "<br />";
	}

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$data['warnings'][$brick] = $warning;
		}
		json_output("warnings", $lang->form_wrong_filled, 0, $data);
	}
	// Zmień hasło
	$salt = get_random_string(8);

	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET password='%s', salt='%s'" .
		"WHERE uid='%d'",
		array(hash_password($pass, $salt), $salt, $user['uid'])
	));

	// LOGING
	log_info("Zmieniono hasło. ID użytkownika: {$user['uid']}.");

	json_output("password_changed", $lang->password_changed, 1);
} else if ($action == "validate_purchase_form") {
	$service_module = $heart->get_service_module($_POST['service']);
	if ($service_module === NULL)
		json_output("wrong_module", $lang->bad_module, 0);

	// Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
	if (!$heart->user_can_use_service($user['uid'], $service_module->service))
		json_output("no_permission", $lang->service_no_permission, 0);

	// Przeprowadzamy walidację danych wprowadzonych w formularzu, a jak zwroci FALSE, to znaczy ze dupa
	if (($return_data = $service_module->validate_purchase_form($_POST)) === FALSE)
		json_output("wrong_module", $lang->bad_module, 0);

	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	if ($return_data['status'] == "warnings") {
		foreach ($return_data['data']['warnings'] as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$return_data['data']['warnings'][$brick] = $warning;
		}
	} else {
		$data_encoded = base64_encode(json_encode($return_data['purchase_data']));
		$return_data['data'] = array(
			'length' => 8000,
			'data' => $data_encoded,
			'sign' => md5($data_encoded . $settings['random_key'])
		);
	}

	json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
} else if ($action == "validate_payment_form") {
	// Sprawdzanie hashu danych przesłanych przez formularz
	if (!isset($_POST['purchase_sign']) || $_POST['purchase_sign'] != md5($_POST['purchase_data'] . $settings['random_key']))
		json_output("wrong_sign", $lang->wrong_sign, 0);

	// Te same dane, co w "payment_form"
	$payment_data = json_decode(base64_decode($_POST['purchase_data']), true);
	$payment_data['method'] = $_POST['method'];
	$payment_data['sms_code'] = $_POST['sms_code'];

	$return_payment = validate_payment($payment_data);
	json_output($return_payment['status'], $return_payment['text'], $return_payment['positive'], $return_payment['data']);
} else if ($action == "refresh_blocks") {
	if (isset($_POST['bricks']))
		$bricks = explode(";", $_POST['bricks']);

	foreach ($bricks as $brick) {
		// Nie ma takiego bloku do odświeżenia
		if (($block = $heart->get_block($brick)) === NULL)
			continue;

		$data[$block->get_content_id()]['content'] = $block->get_content($_GET, $_POST);
		if ($data[$block->get_content_id()]['content'] !== NULL)
			$data[$block->get_content_id()]['class'] = $block->get_content_class();
		else
			$data[$block->get_content_id()]['class'] = "";
	}

	output_page(json_encode($data), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "get_service_long_description") {
	$output = "";
	if (($service_module = $heart->get_service_module($_POST['service'])) !== NULL)
		$output = $service_module->get_full_description();

	output_page($output, "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "get_purchase_info") {
	output_page(purchase_info(array(
		'purchase_id' => $_POST['purchase_id'],
		'action' => "web"
	)), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "form_edit_user_service") {
	if (!is_logged())
		output_page($lang->service_cant_be_modified);

	// Użytkownik nie może edytować usługi
	if (!$settings['user_edit_service'])
		output_page($lang->not_logged);

	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Brak takiej usługi w bazie
	if (!$db->num_rows($result))
		output_page($lang->dont_play_games);

	$player_service = $db->fetch_array_assoc($result);
	// Dany użytkownik nie jest właścicielem usługi o danym id
	if ($player_service['uid'] != $user['uid'])
		output_page($lang->dont_play_games);

	if (($service_module = $heart->get_service_module($player_service['service'])) === NULL)
		output_page($lang->service_cant_be_modified);

	if (($output = $service_module->get_form("user_edit_user_service", $player_service)) === FALSE)
		output_page($lang->service_cant_be_modified);

	eval("\$buttons = \"" . get_template("services/my_services_savencancel") . "\";");

	output_page($buttons . $output);
} else if ($action == "get_user_service_brick") {
	if (!is_logged())
		output_page($lang->not_logged);

	// Sprawdzamy, czy usluga ktora chcemy edytowac jest w bazie
	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Brak takiej usługi w bazie
	if (!$db->num_rows($result))
		output_page($lang->dont_play_games);

	$player_service = $db->fetch_array_assoc($result);
	// Dany użytkownik nie jest właścicielem usługi o danym id
	if ($player_service['uid'] != $user['uid'])
		output_page($lang->dont_play_games);

	if (($service_module = $heart->get_service_module($player_service['service'])) === NULL)
		output_page($lang->service_cant_be_modified);

	if (!class_has_interface($service_module, "IServiceUserEdit"))
		output_page($lang->service_cant_be_modified);

	$button_edit = create_dom_element("img", "", array(
		'class' => "edit_row",
		'src' => "images/pencil.png",
		'title' => $lang->edit,
		'style' => array(
			'height' => '24px'
		)
	));

	output_page($service_module->my_service_info($player_service, $button_edit));
} else if ($action == "edit_user_service") {
	if (!is_logged())
		json_output("not_logged", $lang->not_logged, 0);

	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
		"WHERE `id` = '%d'",
		array($_POST['id'])
	));

	// Brak takiej usługi w bazie
	if (!$db->num_rows($result))
		json_output("dont_play_games", $lang->dont_play_games, 0);

	$user_service = $db->fetch_array_assoc($result);
	// Dany użytkownik nie jest właścicielem usługi o danym id
	if ($user_service['uid'] != $user['uid'])
		json_output("dont_play_games", $lang->dont_play_games, 0);

	if (($service_module = $heart->get_service_module($user_service['service'])) === NULL)
		json_output("wrong_module", $lang->bad_module, 0);

	// Wykonujemy metode edycji usługi gracza na module, który ją obsługuje
	if (!class_has_interface($service_module, "IServiceUserEdit"))
		json_output("service_cant_be_modified", $lang->service_cant_be_modified, 0);

	$return_data = $service_module->user_edit_user_service($_POST, $user_service);

	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	if ($return_data['status'] == "warnings") {
		foreach ($return_data['data']['warnings'] as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$return_data['data']['warnings'][$brick] = $warning;
		}
	}

	json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
} else if ($action == "form_take_over_service") {
	if (($service_module = $heart->get_service_module($_POST['service'])) === NULL || !class_has_interface($service_module, "IServiceTakeOver"))
		output_page($lang->bad_module, "Content-type: text/plain; charset=\"UTF-8\"");

	output_page($service_module->form_take_over_service($_POST['service']), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "take_over_service") {
	if (($service_module = $heart->get_service_module($_POST['service'])) === NULL || !class_has_interface($service_module, "IServiceTakeOver"))
		output_page($lang->bad_module, "Content-type: text/plain; charset=\"UTF-8\"");

	$return_data = $service_module->take_over_service($_POST);

	// Przerabiamy ostrzeżenia, aby lepiej wyglądały
	if ($return_data['status'] == "warnings") {
		foreach ($return_data['data']['warnings'] as $brick => $warning) {
			$warning = create_dom_element("div", $warning, array(
				'class' => "form_warning"
			));
			$return_data['data']['warnings'][$brick] = $warning;
		}
	}

	json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
} else if ($_GET['action'] == "get_income") {
	$user['privilages']['view_income'] = $user['privilages']['acp'] = true;
	$page = new PageAdminIncome();
	output_page($page->get_content($_GET, $_POST), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "execute_service_action") {
	if (($service_module = $heart->get_service_module($_POST['service'])) === NULL || !class_has_interface($service_module, "IServiceExecuteAction"))
		output_page($lang->bad_module, "Content-type: text/plain; charset=\"UTF-8\"");

	output_page($service_module->execute_action($_POST['service_action'], $_POST), "Content-type: text/plain; charset=\"UTF-8\"");
} else if ($action == "get_template") {
	$template = $_POST['template'];
	// Zabezpieczanie wszystkich wartości post
	foreach ($_POST as $key => $value) {
		$_POST[$key] = htmlspecialchars($value);
	}

	if ($template == "register_registered") {
		$username = htmlspecialchars($_POST['username']);
		$email = htmlspecialchars($_POST['email']);
	} else if ($template == "forgotten_password_sent") {
		$username = htmlspecialchars($_POST['username']);
	}

	if (!isset($data['template']))
		eval("\$data['template'] = \"" . get_template("jsonhttp/" . $template) . "\";");

	output_page(json_encode($data), "Content-type: text/plain; charset=\"UTF-8\"");
}

json_output("script_error", "An error occured: no action.");