<?php

if (!defined("IN_SCRIPT")) {
	die("Nie ma tu nic ciekawego.");
}

function get_content($element, $withenvelope = true, $separateclass = false)
{
	global $heart, $db, $user, $lang, $G_PID, $G_PAGE, $settings, $scripts, $stylesheets;

	switch ($element) {

		case "logged_info":
			if (!is_logged())
				break;

			$class = "logged_info";
			eval("\$output = \"" . get_template("logged_in_informations") . "\";");
			break;

		case "wallet":
			if (!is_logged())
				break;

			$class = "wallet_status";
			eval("\$output = \"" . get_template("wallet") . "\";");
			break;

		case "content":
			$class = "content";

			// Pobieramy stronę
			$page = $heart->get_page($G_PID);

			if ($page['must_be_logged'] == 1 && !$user['uid']) {
				$output = $lang['must_be_logged_in'];
				break;
			}

			if ($page['must_be_logged'] == -1 && $user['uid']) {
				$output = $lang['must_be_logged_out'];
				break;
			}

			switch ($G_PID) {
				case "register":
					$result = $db->query(
						"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
						"ORDER BY RAND() " .
						"LIMIT 1"
					);
					$antispam_question = $db->fetch_array_assoc($result);

					$sign = md5($antispam_question['id'] . $settings['random_key']);
					break;

				case "reset_password":
					$code = $_GET['code'];

					// Brak podanego kodu
					if (!strlen($code)) {
						$output = $lang['no_reset_key'];
						break;
					}

					$result = $db->query($db->prepare(
						"SELECT `uid` " .
						"FROM `" . TABLE_PREFIX . "users` " .
						"WHERE `reset_password_key` = '%s'",
						array($code)
					));

					if (!$db->num_rows($result)) { // Nie znalazło użytkownika z takim kodem
						$output = $lang['wrong_reset_key'];
						break;
					}

					$row = $db->fetch_array_assoc($result);
					$sign = md5($row['uid'] . $settings['random_key']);
					break;

				case "transfer_finalized":
					$payment = new Payment($settings['transfer_service']);
					if ($payment->payment_api->check_sign($_GET, $payment->payment_api->data['key'], $_GET['sign']) && $_GET['service'] != $payment->payment_api->data['service']) {
						$output = $lang['transfer_unverified'];
						break;
					}

					// prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
					if (strtoupper($_GET['status']) != 'OK') {
						$output = $lang['transfer_error'];
						break;
					}

					$orderid = htmlspecialchars($_GET['orderid']);
					$amount = number_format($_GET['amount'], 2);

					$output = purchase_info(array(
						'payment' => 'transfer',
						'payment_id' => $_GET['orderid'],
						'action' => 'web'
					));
					break;

				case "purchase":
					$service_module = $heart->get_service_module($_GET['service']);

					if ($service_module === NULL) {
						$output = $lang['site_not_exists'];
						break;
					}

					$heart->page_title .= " - {$service_module->service['name']}";

					// Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
					// Jeżeli wymaga, to to sprawdzamy
					if (class_has_interface($service_module, "IServiceMustBeLogged") && !is_logged()) {
						$output = $lang['must_be_logged_in'];
						break;
					}

					// Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
					if (!$heart->user_can_use_service($user['uid'], $service_module->service)) {
						$output = $lang['service_no_permission'];
						break;
					}

					//
					// Dodajemy opis uslugi

					// Dodajemy długi opis
					if (strlen($service_module->get_full_description()))
						eval("\$show_more = \"" . get_template("services/show_more") . "\";");

					// Dodajemy krótki opis
					eval("\$output = \"" . get_template("services/short_description") . "\";");

					// Dodajemy wyglad formularza zakupu
					if (class_has_interface($service_module, "IServicePurchaseWeb"))
						$output .= $service_module->form_purchase_service();
					else // Nie ma formularza zakupu, to tak jakby strona nie istniała
						$output = $lang['site_not_exists'];

					break;

				case "payment":
					// Sprawdzanie hashu danych przesłanych przez formularz
					if (!isset($_POST['sign']) || $_POST['sign'] != md5($_POST['data'] . $settings['random_key'])) {
						$output = $lang['wrong_sign'];
						break;
					}

					/** Odczytujemy dane, ich format powinien być taki jak poniżej
					 * @param array $data 'service',
					 *						'order'
					 *							...
					 *						'user',
					 *							'uid',
					 *							'email'
					 *							...
					 *						'tariff',
					 *						'cost_transfer'
					 *						'no_sms'
					 *						'no_transfer'
					 *						'no_wallet'
					 */
					$data = json_decode(base64_decode($_POST['data']), true);

					$service_module = $heart->get_service_module($data['service']);
					if ($service_module === NULL || !class_has_interface($service_module, "IServicePurchaseWeb")) {
						$output = $lang['module_is_bad'];
						break;
					}

					// Pobieramy szczegóły zamówienia
					$order_details = $service_module->order_details($data);

					// Pobieramy sposoby płatności
					$payment_methods = "";
					// Sprawdzamy, czy płatność za pomocą SMS jest możliwa
					if ($settings['sms_service'] && isset($data['tariff']) && !$data['no_sms']) {
						$payment_sms = new Payment($settings['sms_service']);
						if (strlen($number = $payment_sms->get_number_by_tariff($data['tariff']))) {
							$tariff['number'] = $number;
							$tariff['cost'] = number_format(get_sms_cost($tariff['number']) * $settings['vat'], 2);
							eval("\$payment_methods .= \"" . get_template("payment_method_sms") . "\";");
						}
					}

					$cost_transfer = number_format($data['cost_transfer'], 2);
					if ($settings['transfer_service'] && isset($data['cost_transfer']) && $data['cost_transfer'] > 1 && !$data['no_transfer'])
						eval("\$payment_methods .= \"" . get_template("payment_method_transfer") . "\";");
					if (is_logged() && isset($data['cost_transfer']) && !$data['no_wallet'])
						eval("\$payment_methods .= \"" . get_template("payment_method_wallet") . "\";");

					$purchase_data = htmlspecialchars($_POST['data']);
					$purchase_sign = htmlspecialchars($_POST['sign']);

					$stylesheets[] = "{$settings['shop_url_slash']}styles/style_payment.css?version=" . VERSION;
					$scripts[] = "{$settings['shop_url_slash']}jscripts/payment.js?version=" . VERSION;

					break;

				case "payment_log":
					$result = $db->query($db->prepare(
						"SELECT SQL_CALC_FOUND_ROWS * " .
						"FROM ({$settings['transactions_query']}) as t " .
						"WHERE t.uid = '%d' " .
						"ORDER BY t.timestamp DESC " .
						"LIMIT " . get_row_limit($G_PAGE, 10),
						array($user['uid'])
					));
					$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

					$payment_logs = "";
					while ($row = $db->fetch_array_assoc($result)) {
						$date = htmlspecialchars($row['timestamp']);
						$cost = htmlspecialchars($row['cost']);
						$cost = number_format(floatval($cost), 2) . " {$settings['currency']}";

						if (($service_module = $heart->get_service_module($row['service'])) !== NULL && class_has_interface($service_module, "IServicePurchaseWeb"))
							$log_info = $service_module->purchase_info("payment_log", $row);

						if (isset($log_info) && $log_info !== FALSE) {
							$desc = $log_info['text'];
							$class = $log_info['class'];
						} else {
							$temp_service = $heart->get_service($row['service']);
							$temp_server = $heart->get_server($row['server']);
							$desc = newsprintf($lang['service_was_bought'], $temp_service['name'], $temp_server['name']);
							$class = "outcome";
							unset($temp_service);
							unset($temp_server);
						}

						//$row['platform'] = htmlspecialchars($row['platform']);
						$row['auth_data'] = htmlspecialchars($row['auth_data']);
						$row['email'] = htmlspecialchars($row['email']);

						eval("\$payment_log_brick = \"" . get_template("payment_log_brick") . "\";");
						$payment_logs .= create_dom_element("div", $payment_log_brick, $data = array(
							'class' => "brick {$class}"
						));
					}

					$pagination = get_pagination($rows_count, $G_PAGE, "index.php", $_GET, 10);
					$pagination_class = $pagination ? "" : "display_none";
					$class = "content";

					break;

				case "my_current_services":
					$my_current_services = "";
					$result = $db->query($db->prepare(
						"SELECT SQL_CALC_FOUND_ROWS * " .
						"FROM `" . TABLE_PREFIX . "players_services` " .
						"WHERE `uid` = '%d' " .
						"ORDER BY `id` DESC " .
						"LIMIT " . get_row_limit($G_PAGE, 4),
						array($user['uid'])
					));
					$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

					while ($row = $db->fetch_array_assoc($result)) {
						if (($service_module = $heart->get_service_module($row['service'])) === NULL)
							continue;

						if ($settings['user_edit_service'] && class_has_interface($service_module, "IServiceUserEdit"))
							$button_edit = create_dom_element("img", "", array(
								'class' => "edit_row",
								'src' => "images/pencil.png",
								'title' => "Edytuj",
								'style' => array(
									'height' => '24px'
								)
							));

						if (($temp_text = $service_module->my_service_info($row, $button_edit)) == "")
							continue;

						$my_current_services .= create_brick($temp_text);
					}

					// Nie znalazło żadnych usług danego gracza
					if (!$my_current_services)
						$my_current_services = $lang['no_data'];

					$pagination = get_pagination($rows_count, $G_PAGE, "index.php", $_GET, 4);
					$pagination_class = $pagination ? "" : "display_none";

					break;

				case "take_over_service":
					$services_options = "";
					$services = $heart->get_services();
					foreach ($services as $service) {
						if (($service_module = $heart->get_service_module($service['id'])) === NULL)
							continue;

						// Moduł danej usługi nie zezwala na jej przejmowanie
						if (!class_has_interface($service_module, "IServiceTakeOver"))
							continue;

						$services_options .= create_dom_element("option", $service['name'], array(
							'value' => $service['id']
						));
					}

					break;
			}

			// Pobranie naglowka zawartosci
			eval("\$page['content_title'] = \"" . get_template("content_title") . "\";");

			if (!strlen($output))
				eval("\$output = \"" . get_template($page['template']) . "\";");

			break;

		case "user_buttons":
			if (is_logged()) {
				$class = "user_buttons";

				// Panel Admina
				if (get_privilages("acp", $user))
					$acp_button = create_dom_element("li", create_dom_element("a", $lang['acp'], array(
						'href' => "admin.php"
					)));

				// Doładowanie portfela
				if ($heart->user_can_use_service($user['uid'], $heart->get_service("charge_wallet")))
					$charge_wallet_button = create_dom_element("li", create_dom_element("a", $lang['charge_wallet'], array(
						'href' => "index.php?pid=purchase&service=charge_wallet"
					)));

				eval("\$output = \"" . get_template("user_buttons") . "\";");
			} else {
				global $login_field;
				$class = "loginarea";
				eval("\$output = \"" . get_template("loginarea") . "\";");
			}

			break;

		case "services_buttons":
			$services = "";
			foreach ($heart->get_services() as $service) {
				if (is_null($service_module = $heart->get_service_module($service['id'])) || !$service_module->show_on_web())
					continue;

				if (!$heart->user_can_use_service($user['uid'], $service))
					continue;

				$services .= create_dom_element("li", create_dom_element("a", $service['name'], array(
					'href' => "index.php?pid=purchase&service=" . urlencode($service['id'])
				)));
			}

			$class = "services_buttons";
			eval("\$output = \"" . get_template("services_buttons") . "\";");

			break;
	}

	if ($withenvelope) {
		// Typ cegły
		if ($element == "wallet") {
			$output = create_dom_element("a", $output, array(
				'id' => $element,
				'class' => $class,
				'href' => "index.php?pid=payment_log"
			));
		} else {
			$output = create_dom_element("div", $output, array(
				'id' => $element,
				'class' => $class
			));
		}
	}

	return $separateclass ? array('content' => $output, 'class' => $class) : $output;
}