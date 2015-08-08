<?php

/**
 * Sprawdza czy jesteśmy w adminowskiej części sklepu
 *
 * @return bool
 */
function admin_session() {
	return in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin"));
}

/**
 * Pobranie szablonu.
 *
 * @param string $title Nazwa szablonu
 * @param bool $install Prawda, jeżeli pobieramy szablon instalacji.
 * @param bool $eslashes Prawda, jeżeli zawartość szablonu ma być "escaped".
 * @param bool $htmlcomments Prawda, jeżeli chcemy dodać komentarze o szablonie.
 * @return string|bool Szablon.
 */
function get_template($title, $install = false, $eslashes = true, $htmlcomments = true)
{
	global $settings, $lang;

	if (!$install) {
		if (strlen($lang->get_current_language_short())) {
			$filename = $title . "." . $lang->get_current_language_short();
			$temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
			if (file_exists($temp))
				$path = $temp;
			else {
				$temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
			}
		}

		if (!isset($path)) {
			$filename = $title;
			$temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
			if (file_exists($temp))
				$path = $temp;
			else {
				$temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
			}
		}
	} else {
		if (strlen($lang->get_current_language_short())) {
			$filename = $title . "." . $lang->get_current_language_short();
			$temp = SCRIPT_ROOT . "install/templates/{$filename}.html";
			if (file_exists($temp))
				$path = $temp;
		}

		if (!isset($path)) {
			$filename = $title;
			$temp = SCRIPT_ROOT . "install/templates/{$filename}.html";
			if (file_exists($temp))
				$path = $temp;
		}
	}

	if (!isset($path))
		return FALSE;

	$template = file_get_contents($path);

	if ($htmlcomments)
		$template = "<!-- start: " . htmlspecialchars($title) . " -->\n{$template}\n<!-- end: " . htmlspecialchars($title) . " -->";

	if ($eslashes)
		$template = str_replace("\\'", "'", addslashes($template));

	$template = str_replace("{__VERSION__}", VERSION, $template);

	return $template;
}

/**
 * Pobranie szablonu
 * @param string $output Zwartość do wyświetlenia
 * @param string $header String do użycia w funkcji header()
 */
function output_page($output, $header = "Content-type: text/html; charset=\"UTF-8\"")
{
	header($header);
	echo $output;
	exit;
}

/**
 * Zwraca treść danego bloku
 *
 * @param string $element
 * @param bool $withenvelope
 * @return string
 */
function get_content($element, $withenvelope = true)
{
	global $heart;

	if (($block = $heart->get_block($element)) === NULL)
		return "";

	return $withenvelope ? $block->get_content_enveloped($_GET, $_POST) : $block->get_content($_GET, $_POST);
}

function get_row_limit($page, $row_limit = 0)
{
	global $settings;
	$row_limit = $row_limit ? $row_limit : $settings['row_limit'];
	return ($page - 1) * $row_limit . "," . $row_limit;
}

function get_pagination($all, $current_page, $script, $get, $row_limit = 0)
{
	global $settings;

	$row_limit = $row_limit ? $row_limit : $settings['row_limit'];

	// Wszystkich elementow jest mniej niz wymagana ilsoc na jednej stronie
	if ($all <= $row_limit)
		return;

	// Pobieramy ilosc stron
	$pages_amount = floor(max($all - 1, 0) / $row_limit) + 1;

	// Poprawiamy obecna strone, gdyby byla bledna
	if ($current_page > $pages_amount)
		$current_page = -1;

	// Usuwamy index "page"
	unset($get['page']);
	$get_string = "";

	// Tworzymy stringa z danych get
	foreach ($get as $key => $value) {
		if (strlen($get_string))
			$get_string .= "&";

		$get_string .= urlencode($key) . "=" . urlencode($value);
	}
	if (strlen($get_string))
		$get_string = "?" . $get_string;

	/*// Pierwsza strona
	$output = create_dom_element("a",1,array(
		'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=1",
		'class'	=> $current_page == 1 ? "current" : ""
	))."&nbsp;";

	// 2 3 ...
	if( $current_page < 5 ) {
		// 2 3
		for($i = 2; $i <= 3; ++$i) {
			$output .= create_dom_element("a",$i,array(
				'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page={$i}"
			))."&nbsp;";
		}

		// Trzy kropki
		$output .= create_dom_element("a","...",array(
				'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".round(($pages_amount-3)/2)
		))."&nbsp;";
	}
	// ...
	else {

	}

	// Ostatnia strona
	$output .= create_dom_element("a",$pages_amount,array(
		'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".$pages_amount,
		'class'	=> $current_page == $pages_amount ? "current" : ""
	))."&nbsp;";*/

	$output = "";
	$lp = 2;
	for ($i = 1, $dots = false; $i <= $pages_amount; ++$i) {
		if ($i != 1 && $i != $pages_amount && ($i < $current_page - $lp || $i > $current_page + $lp)) {
			if (!$dots) {
				if ($i < $current_page - $lp)
					$href = $script . $get_string . (strlen($get_string) ? "&" : "?") . "page=" . round(((1 + $current_page - $lp) / 2));
				else if ($i > $current_page + $lp)
					$href = $script . $get_string . (strlen($get_string) ? "&" : "?") . "page=" . round((($current_page + $lp + $pages_amount) / 2));

				$output .= create_dom_element("a", "...", array(
						'href' => $href
					)) . "&nbsp;";
				$dots = true;
			}
			continue;
		}

		$output .= create_dom_element("a", $i, array(
				'href' => $href = $script . $get_string . (strlen($get_string) ? "&" : "?") .
					"page=" . $i,
				'class' => $current_page == $i ? "current" : ""
			)) . "&nbsp;";
		$dots = false;
	}

	return $output;
}

/* User functions */
/**
 * Sprawddza czy użytkownik jest zalogowany
 *
 * @return bool
 */
function is_logged()
{
	global $user;
	return $user['uid'] ? true : false;
}

function get_privilages($which, $user = array())
{
	// Jeżeli nie podano użytkownika
	if (empty($user))
		global $user;

	if (in_array($which, array("manage_settings", "view_groups", "manage_groups", "view_player_flags",
			"view_player_services", "manage_player_services", "view_income", "view_users", "manage_users",
			"view_sms_codes", "manage_sms_codes", "view_service_codes", "manage_service_codes",
			"view_antispam_questions", "manage_antispam_questions", "view_services", "manage_services",
			"view_servers", "manage_servers", "view_logs", "manage_logs", "update")
	))
		return $user['privilages'][$which] && $user['privilages']['acp'];

	return $user['privilages'][$which];
}

function update_activity($uid)
{
	if (!$uid)
		return;

	global $db;
	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET `lastactiv` = NOW(), `lastip` = '%s' " .
		"WHERE `uid` = '%d'",
		array(get_ip(), $uid)
	));
}

function charge_wallet($uid, $amount)
{
	global $db;
	$db->query($db->prepare(
		"UPDATE `" . TABLE_PREFIX . "users` " .
		"SET `wallet` = `wallet`+'%f' " .
		"WHERE `uid` = '%d'",
		array(number_format($amount, 2), $uid)
	));
}

/**
 * Aktualizuje tabele servers_services
 *
 * @param $data
 */
function update_servers_services($data)
{
	global $db;

	$delete = array();
	$add = array();
	foreach ($data as $arr) {
		if ($arr['status']) {
			$add[] = $db->prepare(
				"('%d', '%s')",
				array($arr['server'], $arr['service'])
			);
		} else {
			$delete[] = $db->prepare(
				"(`server_id` = '%d' AND `service_id` = '%s')",
				array($arr['server'], $arr['service'])
			);
		}
	}

	if (!empty($add)) {
		$db->query(
			"INSERT IGNORE INTO `" . TABLE_PREFIX . "servers_services` (`server_id`, `service_id`) " .
			"VALUES " . implode(", ", $add)
		);
	}

	if (!empty($delete)) {
		$db->query(
			"DELETE FROM `" . TABLE_PREFIX . "servers_services` " .
			"WHERE " . implode(" OR ", $delete)
		);
	}
}

/**
 * @param Entity_Purchase $purchase
 * @return array
 */
function validate_payment($purchase)
{
	global $heart, $settings, $lang;

	$warnings = array();

	// Tworzymy obiekt usługi którą kupujemy
	if (($service_module = $heart->get_service_module($purchase->getService())) === NULL)
		return array(
			'status' => "wrong_module",
			'text' => $lang->bad_module,
			'positive' => false
		);

	if (!in_array($purchase->getPayment('method'), array("sms", "transfer", "wallet", "service_code")))
		return array(
			'status' => "wrong_method",
			'text' => $lang->wrong_payment_method,
			'positive' => false
		);

	// Tworzymy obiekt, który będzie nam obsługiwał proces płatności
	if ($purchase->getPayment('method') == "sms") {
		$transaction_service = if_strlen($purchase->getPayment('sms_service'), $settings['sms_service']);
		$payment = new Payment($transaction_service, $purchase->getUser('platform'));
	} else if ($purchase->getPayment('method') == "transfer") {
		$transaction_service = if_strlen($purchase->getPayment('transfer_service'), $settings['transfer_service']);
		$payment = new Payment($transaction_service, $purchase->getUser('platform'));
	}

	// Pobieramy ile kosztuje ta usługa dla przelewu / portfela
	if ($purchase->getPayment('cost') === NULL)
		$purchase->setPayment(array(
			'cost' => number_format($heart->get_tariff_provision($purchase->getTariff()), 2)
		));

	// Metoda płatności
	if ($purchase->getPayment('method') == "wallet" && !is_logged())
		return array(
			'status' => "wallet_not_logged",
			'text' => $lang->no_login_no_wallet,
			'positive' => false
		);
	else if ($purchase->getPayment('method') == "transfer") {
		if ($purchase->getPayment('cost') <= 1)
			return array(
				'status' => "too_little_for_transfer",
				'text' => $lang->sprintf($lang->transfer_above_amount, $settings['currency']),
				'positive' => false
			);

		if (!$payment->transfer_available())
			return array(
				'status' => "transfer_unavailable",
				'text' => $lang->transfer_unavailable,
				'positive' => false
			);
	} else if ($purchase->getPayment('method') == "sms" && !$payment->sms_available())
		return array(
			'status' => "sms_unavailable",
			'text' => $lang->sms_unavailable,
			'positive' => false
		);
	else if ($purchase->getPayment('method') == "sms" && $purchase->getTariff() && !isset($payment->payment_api->smses[$purchase->getTariff()]))
		return array(
			'status' => "no_sms_option",
			'text' => $lang->no_sms_payment,
			'positive' => false
		);

	// Kod SMS
	$purchase->setPayment(array(
		'sms_code' => trim($purchase->getPayment('sms_code'))
	));
	if ($purchase->getPayment('method') == "sms" && $warning = check_for_warnings("sms_code", $purchase->getPayment('sms_code')))
		$warnings['sms_code'] = array_merge((array)$warnings['sms_code'], $warning);

	// Kod na usługę
	if ($purchase->getPayment('method') == "service_code")
		if (!strlen($purchase->getPayment('service_code')))
			$warnings['service_code'][] = $lang->field_no_empty;

	// Błędy
	if (!empty($warnings)) {
		foreach ($warnings as $brick => $warning) {
			$warning = create_dom_element("div", implode("<br />", $warning), array(
				'class' => "form_warning"
			));
			$warning_data['warnings'][$brick] = $warning;
		}
		return array(
			'status' => "warnings",
			'text' => $lang->form_wrong_filled,
			'positive' => false,
			'data' => $warning_data
		);
	}

	if ($purchase->getPayment('method') == "sms") {
		// Sprawdzamy kod zwrotny
		$sms_return = $payment->pay_sms($purchase->getPayment('sms_code'), $purchase->getTariff(), $purchase->getUser());
		$payment_id = $sms_return['payment_id'];

		if ($sms_return['status'] != "OK")
			return array(
				'status' => $sms_return['status'],
				'text' => $sms_return['text'],
				'positive' => false
			);
	} else if ($purchase->getPayment('method') == "wallet") {
		// Dodanie informacji o płatności z portfela
		$payment_id = pay_wallet($purchase->getPayment('cost'), $purchase->getUser());

		// Metoda pay_wallet zwróciła błąd.
		if (is_array($payment_id))
			return $payment_id;
	}
	else if ($purchase->getPayment('method') == "service_code") {
		// Dodanie informacji o płatności z portfela
		$payment_id = pay_service_code($purchase, $service_module);

		// Funkcja pay_service_code zwróciła błąd.
		if (is_array($payment_id))
			return $payment_id;
	}

	if (in_array($purchase->getPayment('method'), array("wallet", "sms", "service_code"))) {
		// Dokonujemy zakupu usługi
		$purchase->setUser($purchase->getUser());
		$purchase->setPayment(array(
			'payment_id' => $payment_id
		));
		$bought_service_id = $service_module->purchase($purchase);

		return array(
			'status' => "purchased",
			'text' => $lang->purchase_success,
			'positive' => true,
			'data' => array('bsid' => $bought_service_id)
		);
	} else if ($purchase->getPayment('method') == "transfer") {
		// Przygotowujemy dane do przeslania ich do dalszej obróbki w celu stworzenia płatności przelewem
		$purchase_data = array(
			'service' => $service_module->service['id'],
			'email' => $purchase->getEmail(),
			'cost' => $purchase->getPayment('cost'),
			'desc' => $lang->sprintf($lang->payment_for_service, $service_module->service['name']),
			'order' => $purchase->getOrder()
		);

		return $payment->pay_transfer($purchase_data, $purchase->getUser());
	}
}

function pay_by_admin($user)
{
	global $db;

	// Dodawanie informacji o płatności
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "payment_admin` (`aid`, `ip`, `platform`) " .
		"VALUES ('%d', '%s', '%s')",
		array($user['uid'], $user['ip'], $user['platform'])
	));

	return $db->last_id();
}

function pay_wallet($cost, $user)
{
	global $db, $lang;

	// Zostawiamy tylko 2 cyfry po przecinku
	$cost = intval($cost * 100) / 100;

	// Sprawdzanie, czy jest wystarczająca ilość kasy w portfelu
	if ($cost > $user['wallet'])
		return array(
			'status' => "no_money",
			'text' => $lang->not_enough_money,
			'positive' => false
		);

	// Zabieramy kasę z portfela
	charge_wallet($user['uid'], -$cost);

	// Dodajemy informacje o płatności portfelem
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "payment_wallet` " .
		"SET `cost` = '%.2f', `ip` = '%s', `platform` = '%s'",
		array($cost, $user['ip'], $user['platform'])
	));

	return $db->last_id();
}

/**
 * @param Entity_Purchase $purchase
 * @param Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther $service_module
 * @return array|int|string
 */
function pay_service_code($purchase, $service_module)
{
	global $db, $lang, $lang_shop;

	$result = $db->query($db->prepare(
		"SELECT * FROM `" . TABLE_PREFIX . "service_codes` " .
		"WHERE `code` = '%s' " .
		"AND `service` = '%s' " .
		"AND (`server` = '0' OR `server` = '%s') " .
		"AND (`tariff` = '0' OR `tariff` = '%d') " .
		"AND (`uid` = '0' OR `uid` = '%s')",
		array($purchase->getPayment('service_code'), $purchase->getService(), $purchase->getOrder('server'),
			$purchase->getTariff(), $purchase->getUser('uid'))
	));

	if (!$db->num_rows($result))
		return array(
			'status' => "wrong_service_code",
			'text' => $lang->bad_service_code
		);

	while ($row = $db->fetch_array_assoc($result))
		if ($service_module->service_code_validate($purchase, $row)) { // Znalezlismy odpowiedni kod
			$db->query($db->prepare(
				"DELETE FROM `" . TABLE_PREFIX . "service_codes` " .
				"WHERE `id` = '%d'",
				array($row['id'])
			));

			// Dodajemy informacje o płatności kodem
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "payment_code` " .
				"SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
				array($purchase->getPayment('service_code'), $purchase->getUser('ip'), $purchase->getUser('platform'))
			));
			$payment_id = $db->last_id();

			log_info($lang_shop->sprintf($lang_shop->purchase_code, $purchase->getPayment('service_code'),
				$purchase->getUser('username'), $purchase->getUser('uid'), $payment_id));

			return $payment_id;
		}

	return array(
		'status' => "wrong_service_code",
		'text' => $lang->bad_service_code
	);
}

/**
 * Add information about purchasing a service
 *
 * @param integer $uid
 * @param string $user_name
 * @param string $ip
 * @param string $method
 * @param string $payment_id
 * @param string $service
 * @param integer $server
 * @param string $amount
 * @param string $auth_data
 * @param string $email
 * @param array $extra_data
 * @return int|string
 */
function add_bought_service_info($uid, $user_name, $ip, $method, $payment_id, $service, $server, $amount, $auth_data, $email, $extra_data)
{
	global $heart, $db, $lang, $lang_shop;

	// Dodajemy informacje o kupionej usludze do bazy danych
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "bought_services` " .
		"SET `uid` = '%d', `payment` = '%s', `payment_id` = '%s', `service` = '%s', " .
		"`server` = '%d', `amount` = '%s', `auth_data` = '%s', `email` = '%s', `extra_data` = '%s'",
		array($uid, $method, $payment_id, $service, $server, $amount, $auth_data, $email, json_encode($extra_data))
	));
	$bougt_service_id = $db->last_id();

	$ret = $lang->none;
	if (strlen($email)) {
		$message = purchase_info(array(
			'purchase_id' => $bougt_service_id,
			'action' => "email"
		));
		if (strlen($message)) {
			$title = ($service == 'charge_wallet' ? $lang->charge_wallet : $lang->purchase);
			$ret = send_email($email, $auth_data, $title, $message);
		}

		if ($ret == "not_sent")
			$ret = "nie wysłano";
		else if ($ret == "sent")
			$ret = "wysłano";
	}

	$temp_service = $heart->get_service($service);
	$temp_server = $heart->get_server($server);
	$amount = $amount != -1 ? "{$amount} {$temp_service['tag']}" : $lang->forever;
	log_info($lang_shop->sprintf($lang_shop->bought_service_info, $service, $auth_data, $amount, $temp_server['name'], $payment_id, $ret, $user_name, $uid, $ip));
	unset($temp_server);

	return $bougt_service_id;
}

//
// $data:
// 	purchase_id - id zakupu
// 	payment - metoda płatności
// 	payment_id - id płatności
// 	action - jak sformatowac dane
//
function purchase_info($data)
{
	global $heart, $db, $settings;

	// Wyszukujemy po id zakupu
	if (isset($data['purchase_id']))
		$where = $db->prepare("t.id = '%d'", array($data['purchase_id']));
	// Wyszukujemy po id płatności
	else if (isset($data['payment']) && isset($data['payment_id']))
		$where = $db->prepare(
			"t.payment = '%s' AND t.payment_id = '%s'",
			array($data['payment'], $data['payment_id'])
		);
	else
		return "";

	$pbs = $db->fetch_array_assoc($db->query(
		"SELECT * FROM ({$settings['transactions_query']}) as t " .
		"WHERE {$where}"
	));

	// Brak wynikow
	if (empty($pbs))
		return "Brak zakupu w bazie.";

	$service_module = $heart->get_service_module($pbs['service']);
	return $service_module !== NULL && object_implements($service_module, "IService_PurchaseWeb") ? $service_module->purchase_info($data['action'], $pbs) : "";
}

function delete_users_old_services()
{
	global $heart, $db, $settings, $lang_shop;
	// Usunięcie przestarzałych usług gracza
	// Pierwsze pobieramy te, które usuniemy
	// Potem je usuwamy, a następnie wywołujemy akcje na module
	$result = $db->query(
		"SELECT ps.*, UNIX_TIMESTAMP() as `now` " .
		"FROM `" . TABLE_PREFIX . "players_services` AS ps " .
		"WHERE `expire` < UNIX_TIMESTAMP() AND `expire` != '-1'"
	);

	$delete_ids = array();
	$users_services = array();
	while ($row = $db->fetch_array_assoc($result)) {
		if (($service_module = $heart->get_service_module($row['service'])) === NULL)
			continue;

		if ($service_module->user_service_delete($row)) {
			$delete_ids[] = $row['id'];
			$users_services[] = $row;
			log_info($lang_shop->sprintf($lang_shop->expired_service_delete, $row['auth_data'], $row['server'], $row['service'], get_type_name($row['type'])));
		}
	}

	// Usuwamy usugi ktre zwróciły true
	if (!empty($delete_ids))
		$db->query($db->prepare(
			"DELETE FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `id` IN (" . implode(", ", $delete_ids) . ")",
			array()
		));

	// Wywołujemy akcje po usunieciu
	foreach ($users_services as $user_service) {
		if (($service_module = $heart->get_service_module($user_service['service'])) === NULL)
			continue;

		$service_module->user_service_delete_post($user_service);
	}

	// Usunięcie przestarzałych flag graczy
	// Tak jakby co
	$db->query(
		"DELETE FROM `" . TABLE_PREFIX . "players_flags` " .
		"WHERE (`a` < UNIX_TIMESTAMP() AND `a` != '-1') " .
		"AND (`b` < UNIX_TIMESTAMP() AND `b` != '-1') " .
		"AND (`c` < UNIX_TIMESTAMP() AND `c` != '-1') " .
		"AND (`d` < UNIX_TIMESTAMP() AND `d` != '-1') " .
		"AND (`e` < UNIX_TIMESTAMP() AND `e` != '-1') " .
		"AND (`f` < UNIX_TIMESTAMP() AND `f` != '-1') " .
		"AND (`g` < UNIX_TIMESTAMP() AND `g` != '-1') " .
		"AND (`h` < UNIX_TIMESTAMP() AND `h` != '-1') " .
		"AND (`i` < UNIX_TIMESTAMP() AND `i` != '-1') " .
		"AND (`j` < UNIX_TIMESTAMP() AND `j` != '-1') " .
		"AND (`k` < UNIX_TIMESTAMP() AND `k` != '-1') " .
		"AND (`l` < UNIX_TIMESTAMP() AND `l` != '-1') " .
		"AND (`m` < UNIX_TIMESTAMP() AND `m` != '-1') " .
		"AND (`n` < UNIX_TIMESTAMP() AND `n` != '-1') " .
		"AND (`o` < UNIX_TIMESTAMP() AND `o` != '-1') " .
		"AND (`p` < UNIX_TIMESTAMP() AND `p` != '-1') " .
		"AND (`q` < UNIX_TIMESTAMP() AND `q` != '-1') " .
		"AND (`r` < UNIX_TIMESTAMP() AND `r` != '-1') " .
		"AND (`s` < UNIX_TIMESTAMP() AND `s` != '-1') " .
		"AND (`t` < UNIX_TIMESTAMP() AND `t` != '-1') " .
		"AND (`u` < UNIX_TIMESTAMP() AND `u` != '-1') " .
		"AND (`v` < UNIX_TIMESTAMP() AND `v` != '-1') " .
		"AND (`w` < UNIX_TIMESTAMP() AND `w` != '-1') " .
		"AND (`x` < UNIX_TIMESTAMP() AND `x` != '-1') " .
		"AND (`y` < UNIX_TIMESTAMP() AND `y` != '-1') " .
		"AND (`z` < UNIX_TIMESTAMP() AND `z` != '-1')"
	);

	// Usuwamy przestarzałe logi
	if (intval($settings['delete_logs']) != 0)
		$db->query($db->prepare(
			"DELETE FROM `" . TABLE_PREFIX . "logs` " .
			"WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL '%d' DAY)",
			array($settings['delete_logs'])
		));
}

function send_email($email, $name, $subject, $text)
{
	global $settings, $lang_shop;

	////////// USTAWIENIA //////////
	$email = filter_var($email, FILTER_VALIDATE_EMAIL);    // Adres e-mail adresata
	$name = htmlspecialchars($name);
	$sender_email = $settings['sender_email'];
	$sender_name = $settings['sender_email_name'];

	if (!strlen($email))
		return "wrong_email";

	$header = "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: text/html; charset=UTF-8\n";
	$header .= "From: {$sender_name} < {$sender_email} >\n";
	$header .= "To: {$name} < {$email} >\n";
	$header .= "X-Sender: {$sender_name} < {$sender_email} >\n";
	$header .= 'X-Mailer: PHP/' . phpversion();
	$header .= "X-Priority: 1 (Highest)\n";
	$header .= "X-MSMail-Priority: High\n";
	$header .= "Importance: High\n";
	$header .= "Return-Path: {$sender_email}\n"; // Return path for errors

	if (!mail($email, $subject, $text, $header))
		return "not_sent";

	log_info($lang_shop->sprintf($lang_shop->email_was_sent, $email, $text));
	return "sent";
}

function log_info($string)
{
	global $db;
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "logs` " .
		"SET `text` = '%s'",
		array($string)
	));
}

/**
 * Sprawdza, czy dany obiekt implementuje odpowiedni interfejs
 *
 * @param $class
 * @param $interface
 * @return bool
 */
function object_implements($class, $interface)
{
	$interfaces = class_implements($class);
	return in_array($interface, $interfaces);
}

function myErrorHandler($errno, $string, $errfile, $errline)
{
	global $settings, $lang, $templates;

	switch ($errno) {
		case E_USER_ERROR:
			$array = json_decode($string, true);
			$array['message'] = $lang->mysqli[$array['message_id']]; // Pobieramy odpowiednik z bilioteki jezykowej
			$header = eval($templates->render("header_error"));
			$message = eval($templates->render("error_handler"));

			if (strlen($array['query'])) {
				$text = date($settings['date_format']) . ": " . $array['query'];
				if (!file_exists(SQL_LOG) || !strlen(file_get_contents(SQL_LOG))) file_put_contents(SQL_LOG, $text);
				else file_put_contents(SQL_LOG, file_get_contents(SQL_LOG) . "\n\n" . $text);
			}

			output_page($message);
			exit;
			break;

		default:
			break;
	}

	/* Don't execute PHP internal error handler */
	if (!(error_reporting() & $errno))
		return false;
	else
		return true;
}

function create_dom_element($name, $text = "", $data = array())
{
	$features = "";
	foreach ($data as $key => $value) {
		if (is_array($value) || !strlen($value))
			continue;

		$features .= (strlen($features) ? " " : "") . $key . '="' . str_replace('"', '\"', $value) . '"';
	}

	$style = "";
	foreach ($data['style'] as $key => $value) {
		if (!strlen($value))
			continue;

		$style .= (strlen($style) ? "; " : "") . "{$key}: {$value}";
	}
	if (strlen($style))
		$features .= (strlen($features) ? " " : "") . "style=\"{$style}\"";

	$name_hsafe = htmlspecialchars($name);
	$output = "<{$name_hsafe} {$features}>";
	if (strlen($text))
		$output .= $text;

	if (!in_array($name, array("input", "img")))
		$output .= "</{$name_hsafe}>";

	return $output;
}

function create_brick($text, $class = "", $alpha = 0.2)
{
	$brick_r = rand(0, 255);
	$brick_g = rand(0, 255);
	$brick_b = rand(0, 255);
	return create_dom_element("div", $text, array(
		'class' => "brick" . ($class ? " {$class}" : ""),
		'style' => array(
			'border-color' => "rgb({$brick_r},{$brick_g},{$brick_b})",
			'background-color' => "rgba({$brick_r},{$brick_g},{$brick_b},{$alpha})"
		)
	));
}

function get_platform($platform)
{
	global $lang;

	if ($platform == "engine_amxx") return $lang->amxx_server;
	else if ($platform == "engine_sm") return $lang->sm_server;

	return $platform;
}

// Zwraca nazwę typu
function get_type_name($value)
{
	global $lang;

	if ($value == TYPE_NICK)
		return $lang->nickpass;
	else if ($value == TYPE_IP)
		return $lang->ippass;
	else if ($value == TYPE_SID)
		return $lang->sid;

	return "";
}

function get_ip()
{
	if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$cf_ip_ranges = array('204.93.240.0/24', '204.93.177.0/24', '199.27.128.0/21', '173.245.48.0/20', '103.21.244.0/22',
			'103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
			'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15');

		foreach ($cf_ip_ranges as $range)
			if (ip_in_range($_SERVER['REMOTE_ADDR'], $range))
				return $_SERVER['HTTP_CF_CONNECTING_IP'];
	}

	return $_SERVER['REMOTE_ADDR'];
}

/**
 * Zwraca datę w odpowiednim formacie
 *
 * @param integer|string $timestamp
 * @param string $format
 * @return string
 */
function convertDate($timestamp, $format="")
{
	if (!strlen($format)) {
		global $settings;
		$format = $settings['date_format'];
	}

	$date = new DateTime($timestamp);
	return $date->format($format);
}

function get_sms_cost($number)
{
	if (strlen($number) < 4)
		return 0;
	else if ($number[0] == "7")
		return $number[1] == "0" ? 0.5 : intval($number[1]);
	else if ($number[0] == "9")
		return intval($number[1] . $number[2]);

	return 0;
}

function hash_password($password, $salt)
{
	return md5(md5($password) . md5($salt));
}

function escape_filename($filename)
{
	$filename = str_replace('/', '_', $filename);
	$filename = str_replace(' ', '_', $filename);
	$filename = str_replace('.', '_', $filename);
	return $filename;
}

function get_random_string($length)
{
	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"; //length:36
	$final_rand = "";
	for ($i = 0; $i < $length; $i++)
		$final_rand .= $chars[rand(0, strlen($chars) - 1)];

	return $final_rand;
}

function valid_steam($steamid)
{
	return preg_match('/\bSTEAM_([0-9]{1}):([0-9]{1}):([0-9])+$/', $steamid) ? '1' : '0';
}

function secondsToTime($seconds)
{
	global $lang;

	$dtF = new DateTime("@0");
	$dtT = new DateTime("@$seconds");
	return $dtF->diff($dtT)->format("%a {$lang->days} {$lang->and} %h {$lang->hours}");
}

function if_isset(&$isset, $default)
{
	return isset($isset) ? $isset : $default;
}

function if_strlen(&$empty, $default)
{
	return isset($empty) && strlen($empty) ? $empty : $default;
}

function mb_str_split($string)
{
	return preg_split('/(?<!^)(?!$)/u', $string);
}

function searchWhere($search_ids, $search, &$where)
{
	global $db;

	$search_where = array();
	$search_like = $db->escape('%' . implode('%', mb_str_split($search)) . '%');

	foreach ($search_ids as $search_id)
		$search_where[] = "{$search_id} LIKE '{$search_like}'";

	if (!empty($search_where)) {
		$search_where = implode(" OR ", $search_where);
		if (strlen($where))
			$where .= " AND ";

		$where .= "( {$search_where} )";
	}
}

/**
 * @param $url - adres url
 * @param int $timeout - po jakim czasie ma przerwać
 * @return string
 */
function curl_get_contents($url, $timeout = 10)
{
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $url,
		CURLOPT_TIMEOUT => $timeout
	));
	$resp = curl_exec($curl);
	curl_close($curl);

	return $resp;
}

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range)
{
	if (strpos($range, '/') !== false) {
		// $range is in IP/NETMASK format
		list($range, $netmask) = explode('/', $range, 2);
		if (strpos($netmask, '.') !== false) {
			// $netmask is a 255.255.0.0 format
			$netmask = str_replace('*', '0', $netmask);
			$netmask_dec = ip2long($netmask);
			return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
		} else {
			// $netmask is a CIDR size block
			// fix the range argument
			$x = explode('.', $range);
			while (count($x) < 4) $x[] = '0';
			list($a, $b, $c, $d) = $x;
			$range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
			$range_dec = ip2long($range);
			$ip_dec = ip2long($ip);

			# Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
			#$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

			# Strategy 2 - Use math to create it
			$wildcard_dec = pow(2, (32 - $netmask)) - 1;
			$netmask_dec = ~$wildcard_dec;

			return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
		}
	} else {
		// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
		if (strpos($range, '*') !== false) { // a.b.*.* format
			// Just convert to A-B format by setting * to 0 for A and 255 for B
			$lower = str_replace('*', '0', $range);
			$upper = str_replace('*', '255', $range);
			$range = "$lower-$upper";
		}

		if (strpos($range, '-') !== false) { // A-B format
			list($lower, $upper) = explode('-', $range, 2);
			$lower_dec = (float)sprintf("%u", ip2long($lower));
			$upper_dec = (float)sprintf("%u", ip2long($upper));
			$ip_dec = (float)sprintf("%u", ip2long($ip));
			return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
		}
		return false;
	}
}

function ends_at($string, $end) {
	return substr($string, -strlen($end)) == $end;
}

/**
 * Prints var_dump in pre
 *
 * @param mixed $a
 */
function pr($a) {
	echo "<pre>";
	var_dump($a);
	echo "</pre>";
}

/**
 * @param mixed $val
 * @return bool
 */
function is_integer($val) {
	return strlen($val) && trim($val) === strval(intval($val));
}