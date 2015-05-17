<?php

if (!defined("IN_SCRIPT")) {
	die("Nie ma tu nic ciekawego.");
}

function add_note($text, $class)
{
	global $gl_Notes;

	eval("\$gl_Notes .= \"" . get_template("admin/note") . "\";");
}

function get_content($element, $withenvelope = true, $separateclass = false)
{
	global $heart, $db, $user, $lang, $settings, $s_Flags, $G_PID, $G_PAGE, $gl_Notes, $scripts;

	switch ($element) {
		case "main_content":
			if (!get_privilages("acp")) {
				$output = $lang['no_privilages'];
				break;
			}

			$bricks = "";
			// Info o serwerach
			$bricks .= create_brick(newsprintf($lang['amount_of_servers'], $heart->get_servers_amount()), "brick_pa_main");
			// Info o użytkownikach
			$bricks .= create_brick(newsprintf($lang['amount_of_users'], $db->get_column("SELECT COUNT(*) FROM `" . TABLE_PREFIX . "users`", "COUNT(*)")), "brick_pa_main");
			// Info o kupionych usługach
			$amount = $db->get_column(
				"SELECT COUNT(*) " .
				"FROM ({$settings['transactions_query']}) AS t",
				"COUNT(*)"
			);
			$bricks .= create_brick(newsprintf($lang['amount_of_bought_services'], $amount), "brick_pa_main");
			// Info o wysłanych smsach
			$amount = $db->get_column(
				"SELECT COUNT(*) AS `amount` " .
				"FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.payment = 'sms' AND t.free='0'",
				"amount"
			);
			$bricks .= create_brick(newsprintf($lang['amount_of_sent_smses'], $amount), "brick_pa_main");

			// Pobranie wyglądu strony
			eval("\$output = \"" . get_template("admin/main_content") . "\";");
			break;

		case "settings":
			if (!get_privilages("manage_settings")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie listy serwisów transakcyjnych
			$result = $db->query(
				"SELECT id, name, sms, transfer " .
				"FROM `" . TABLE_PREFIX . "transaction_services`"
			);
			$sms_services = $transfer_services = "";
			while ($row = $db->fetch_array_assoc($result)) {
				if ($row['sms']) {
					$sms_services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id'],
						'selected' => $row['id'] == $settings['sms_service'] ? "selected" : ""
					));
				}
				if ($row['transfer']) {
					$transfer_services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id'],
						'selected' => $row['id'] == $settings['transfer_service'] ? "selected" : ""
					));
				}
			}
			$cron[$settings['cron_each_visit'] ? "yes" : "no"] = "selected";
			$user_edit_service[$settings['user_edit_service'] ? "yes" : "no"] = "selected";

			// Pobieranie listy dostępnych szablonów
			$dirlist = scandir(SCRIPT_ROOT . "themes");
			$themes_list = "";
			foreach ($dirlist as $dir_name) {
				if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "themes/{$dir_name}")) {
					$themes_list .= create_dom_element("option", $dir_name, array(
						'value' => $dir_name,
						'selected' => $dir_name == $settings['theme'] ? "selected" : ""
					));
				}
			}

			// Pobieranie listy dostępnych języków
			$dirlist = scandir(SCRIPT_ROOT . "includes/languages");
			$languages_list = "";
			foreach ($dirlist as $dir_name) {
				if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "includes/languages/{$dir_name}")) {
					$languages_list .= create_dom_element("option", $lang['languages'][$dir_name], array(
						'value' => $dir_name,
						'selected' => $dir_name == $settings['language'] ? "selected" : ""
					));
				}
			}

			// Pobranie wyglądu strony
			$title = "Ustawienia Sklepu";
			eval("\$output = \"" . get_template("admin/settings") . "\";");
			break;

		case "antispam_questions":
			if (!get_privilages("view_antispam_questions")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie taryf
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM `" . TABLE_PREFIX . "antispam_questions` " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Pobranie przycisku edycji oraz usuwania

				if (get_privilages("manage_antispam_questions")) {
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => "Edytuj {$row['tariff']}"
					));

					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['tariff']}"
					));
				} else
					$button_delete = $button_edit = "";

				// Zabezpieczanie danych
				$row['answers'] = htmlspecialchars($row['answers']);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/antispam_questions_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			if (get_privilages("manage_antispam_questions")) {
				// Pobranie przycisku dodającego taryfę
				$button = array(
					'id' => "button_add_antispam_question",
					'value' => $lang['add_antispam_question']
				);
				eval("\$buttons = \"" . get_template("admin/button") . "\";");
			}

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/antispam_questions_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['antispam_questions'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "transaction_services":
			if (!get_privilages("manage_settings")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie listy serwisów transakcyjnych
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM `" . TABLE_PREFIX . "transaction_services` " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				$row['sms'] = $row['sms'] ? "TAK" : "NIE";
				$row['transfer'] = $row['transfer'] ? "TAK" : "NIE";

				// Pobranie przycisku edycji
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => "Edytuj {$row['name']}"
				));

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/transaction_services_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/transaction_services_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['transaction_services'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "services":
			if (!get_privilages("view_services")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie listy serwisów transakcyjnych
			$i = 0;
			$tbody = "";
			foreach ($heart->get_services() as $row) {
				$i += 1;
				$row['id'] = htmlspecialchars($row['id']);
				$row['name'] = htmlspecialchars($row['name']);
				$row['short_description'] = htmlspecialchars($row['short_description']);
				$row['description'] = htmlspecialchars($row['description']);

				if (get_privilages("manage_services")) {
					// Pobranie przycisku edycji
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => "Edytuj {$row['name']}"
					));
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['name']}"
					));
				} else
					$button_delete = $button_edit = "";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/services_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/services_thead") . "\";");

			if (get_privilages("manage_services")) {
				// Pobranie przycisku dodającego taryfę
				$button = array(
					'id' => "button_add_service",
					'value' => $lang['add_service']);
				eval("\$buttons = \"" . get_template("admin/button") . "\";");
			}

			// Pobranie struktury tabeli
			$title = $lang['services'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "servers":
			if (!get_privilages("manage_servers")) {
				$output = $lang['no_privilages'];
				break;
			}

			$i = 0;
			$tbody = "";
			foreach ($heart->get_servers() as $row) {
				$i += 1;
				$row['name'] = htmlspecialchars($row['name']);
				$row['ip'] = htmlspecialchars($row['ip']);
				$row['port'] = htmlspecialchars($row['port']);

				if (get_privilages("manage_servers")) {
					// Pobranie przycisku edycji
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => "Edytuj {$row['name']}"
					));
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['name']}"
					));
				} else
					$button_delete = $button_edit = "";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/servers_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/servers_thead") . "\";");

			if (get_privilages("manage_servers")) {
				// Pobranie przycisku dodającego taryfę
				$button = array(
					'id' => "button_add_server",
					'value' => $lang['add_server']);
				eval("\$buttons = \"" . get_template("admin/button") . "\";");
			}

			// Pobranie struktury tabeli
			$title = $lang['servers'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");

			break;

		case "tariffs":
			if (!get_privilages("manage_settings")) {
				$output = $lang['no_privilages'];
				break;
			}

			$i = 0;
			$tbody = "";
			foreach ($heart->get_tariffs() as $tariff_data) {
				$i += 1;
				// Pobranie przycisku edycji oraz usuwania
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => "Edytuj {$tariff_data['tariff']}"
				));
				if (!$tariff_data['predefined']) {
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$tariff_data['tariff']}"
					));
				} else
					$button_delete = "";

				$provision = number_format($tariff_data['provision'], 2);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/tariffs_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie przycisku dodającego taryfę
			$button = array(
				'id' => "button_add_tariff",
				'value' => $lang['add_tariff']
			);
			eval("\$buttons = \"" . get_template("admin/button") . "\";");

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/tariffs_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['tariffs'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "pricelist":
			if (!get_privilages("manage_settings")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie cen
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM `" . TABLE_PREFIX . "pricelist` " .
				"ORDER BY `service`, `server`, `tariff` " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Pobranie przycisku edycji oraz usuwania
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => "Edytuj {$row['tariff']}"
				));
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => "Usuń {$row['tariff']}"
				));

				if ($row['server'] != -1) {
					$temp_server = $heart->get_server($row['server']);
					$row['server'] = $temp_server['name'];
					unset($temp_server);
				} else
					$row['server'] = "Wszystkie";
				$service = $heart->get_service($row['service']);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/pricelist_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie przycisku dodającego taryfę
			$button = array(
				'id' => "button_add_price",
				'value' => $lang['add_price']
			);
			eval("\$buttons = \"" . get_template("admin/button") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/pricelist_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['pricelist'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "players_flags":
			if (!get_privilages("view_player_flags")) {
				$output = $lang['no_privilages'];
				break;
			}

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "players_flags` " .
				"ORDER BY `id` DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Zabezpieczanie danych
				$row['auth_data'] = htmlspecialchars($row['auth_data']);

				// Zamiana dat
				for ($j = 0; $j < strlen($s_Flags); ++$j) {
					if (!$row[$s_Flags[$j]])
						$row[$s_Flags[$j]] = " ";
					else if ($row[$s_Flags[$j]] == -1)
						$row[$s_Flags[$j]] = $lang['never'];
					else
						$row[$s_Flags[$j]] = date($settings['date_format'], $row[$s_Flags[$j]]);
				}

				// Pobranie danych serwera
				$temp_server = $heart->get_server($row['server']);
				$row['server'] = $temp_server['name'];
				unset($temp_server);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/players_flags_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/players_flags_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['players_flags'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "players_services":
			if (!get_privilages("view_player_services")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Wyszukujemy dane ktore spelniaja kryteria
			if (isset($_GET['search'])) {
				searchWhere(array("ps.id", "ps.uid", "u.username", "srv.name", "s.name", "ps.auth_data"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if (strlen($where))
				$where = "WHERE {$where} ";

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS ps.id AS `id`, ps.uid AS `uid`, u.username AS `username`, srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
				"ps.type AS `type`, ps.auth_data AS `auth_data`, ps.expire AS `expire` " .
				"FROM `" . TABLE_PREFIX . "players_services` AS ps " .
				"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON s.id = ps.service " .
				"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON srv.id = ps.server " .
				"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = ps.uid " .
				"{$where}" .
				"ORDER BY `id` DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Zabezpieczanie danych
				$row['auth_data'] = htmlspecialchars($row['auth_data']);
				$row['service'] = htmlspecialchars($row['service']);
				$row['server'] = htmlspecialchars($row['server']);
				$row['username'] = htmlspecialchars($row['username']);

				// Zamiana daty
				$row['expire'] = $row['expire'] == -1 ? $lang['never'] : date($settings['date_format'], $row['expire']);

				// Pobranie przycisku edycji oraz usuwania
				if (get_privilages("manage_player_services")) {
					if (($service_module = $heart->get_service_module($row['service_id'])) !== NULL
						&& class_has_interface($service_module, "IServiceAdminManageUserService"))
						$button_edit = create_dom_element("img", "", array(
							'id' => "edit_row_{$i}",
							'src' => "images/edit.png",
							'title' => "Edytuj {$row['id']}"
						));
					else
						$button_edit = "";

					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['id']}"
					));
				} else
					$button_edit = $button_delete = "";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/players_services_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie przycisku dodajacego flagi
			if (get_privilages("manage_player_services")) {
				$buttons .= create_dom_element("input", "", array(
					'id' => "button_add_user_service",
					'type' => "button",
					'value' => $lang['add_user_service']
				));
			}

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/players_services_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['players_services'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");

			$scripts[] = "{$settings['shop_url_slash']}jscripts/services/extra_flags.js?version=" . VERSION;

			break;

		case "bought_services":
			// Wyszukujemy dane ktore spelniaja kryteria
			if (isset($_GET['search'])) {
				searchWhere(array("t.id", "t.payment", "t.payment_id", "t.uid", "t.ip", "t.email", "t.auth_data", "CAST(t.timestamp as CHAR)"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if (strlen($where))
				$where = "WHERE {$where} ";

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM ({$settings['transactions_query']}) as t " .
				$where .
				"ORDER BY t.timestamp DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				//$row['platform'] = htmlspecialchars($row['platform']);
				$row['auth_data'] = htmlspecialchars($row['auth_data']);
				$row['email'] = htmlspecialchars($row['email']);
				$username = htmlspecialchars($row['username']);

				// Pobranie danych o usłudze, która została kupiona
				$service = $heart->get_service($row['service']);

				// Pobranie danych o serwerze na ktorym zostala wykupiona usługa
				$server = $heart->get_server($row['server']);

				// Przerobienie ilosci
				$amount = $row['amount'] != -1 ? "{$row['amount']} {$service['tag']}" : $lang['forever'];

				// Rozkulbaczenie extra daty
				$row['extra_data'] = json_decode($row['extra_data'], true);
				$extra_data = array();
				foreach ($row['extra_data'] as $key => $value) {
					if ($value == "")
						continue;

					$value = htmlspecialchars($value);

					if ($key == "password")
						$key = $lang['password'];
					else if ($key == "type") {
						$key = $lang['type'];
						$value = get_type_name($value);
					}

					$extra_data[] = "{$key}: {$value}";
				}
				$row['extra_data'] = implode("<br />", $extra_data);

				// Pobranie linku płatności
				$payment_link = "admin.php?pid=payment_{$row['payment']}&payid={$row['payment_id']}&highlight=1";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/bought_services_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/bought_services_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['bought_services'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "payment_sms":
			$where = "( t.payment = 'sms' ) ";

			// Wyszukujemy platnosci o konkretnym ID
			if (isset($_GET['payid'])) {
				if ($where != "") $where .= " AND ";

				$where .= $db->prepare("( t.payment_id = '%s' ) ", array($_GET['payid']));

				// Podświetlenie konkretnej płatności
				//$row['class'] = "highlighted";
			} // Wyszukujemy dane ktore spelniaja kryteria
			else if (isset($_GET['search'])) {
				searchWhere(array("t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if ($where != "") $where = "WHERE {$where} ";

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM ({$settings['transactions_query']}) as t " .
				$where .
				"ORDER BY t.timestamp DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$row['free'] = $row['free'] ? "TAK" : "NIE";
				$row['income'] = $row['income'] ? number_format($row['income'], 2) . " " . $settings['currency'] : "";
				$row['cost'] = $row['cost'] ? number_format($row['cost'], 2) . " " . $settings['currency'] : "";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/payment_sms_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/payment_sms_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['payment_sms'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "payment_transfer":
			$where = "( t.payment = 'transfer' ) ";

			// Wyszukujemy dane ktore spelniaja kryteria
			if (isset($_GET['search'])) {
				searchWhere(array("t.payment_id", "t.income", "t.ip"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if ($where != "") $where = "WHERE {$where} ";

			// Wykonujemy zapytanie
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM ({$settings['transactions_query']}) as t " .
				$where .
				"ORDER BY t.timestamp DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			// Pobieramy dane
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$row['income'] = $row['income'] ? number_format($row['income'], 2) . " " . $settings['currency'] : "";

				// Podświetlenie konkretnej płatności
				if ($_GET['highlight'] && $_GET['payid'] == $row['payment_id']) {
					$row['class'] = "highlighted";
				}

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/payment_transfer_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/payment_transfer_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['payment_transfer'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "payment_wallet":
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.payment = 'wallet' " .
				"ORDER BY t.timestamp DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$row['cost'] = $row['cost'] ? number_format($row['cost'], 2) . " " . $settings['currency'] : "";

				// Podświetlenie konkretnej płatności
				if ($_GET['highlight'] && $_GET['payid'] == $row['payment_id']) {
					$row['class'] = "highlighted";
				}

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/payment_wallet_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/payment_wallet_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['payment_wallet'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "payment_admin":
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.payment = 'admin' " .
				"ORDER BY t.timestamp DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				// Podświetlenie konkretnej płatności
				if ($_GET['highlight'] && $_GET['payid'] == $row['payment_id']) {
					$row['class'] = "highlighted";
				}

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/payment_admin_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/payment_admin_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['payment_admin'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "users":
			if (!get_privilages("view_users")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Wyszukujemy dane ktore spelniaja kryteria
			if (isset($_GET['search'])) {
				searchWhere(array("`uid`", "`username`", "`forename`", "`surname`", "`email`", "`groups`", "`wallet`"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if ($where != "") $where = "WHERE {$where} ";

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `forename`, `surname`, `email`, `groups`, `wallet` " .
				"FROM `" . TABLE_PREFIX . "users` " .
				$where .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Zabezpieczanie danych
				$row['username'] = htmlspecialchars($row['username']);
				$row['email'] = htmlspecialchars($row['email']);
				$row['forename'] = htmlspecialchars($row['forename']);
				$row['surname'] = htmlspecialchars($row['surname']);
				$row['wallet'] = number_format($row['wallet'], 2);


				$row['groups'] = explode(";", $row['groups']);
				$groups = array();
				foreach ($row['groups'] as $gid) {
					$group = $heart->get_group($gid);
					$groups[] = "{$group['name']} ({$group['id']})";
				}
				$groups = implode("; ", $groups);

				// Pobranie przycisku doładowania portfela
				if (get_privilages("manage_users")) {
					eval("\$button_charge = \"" . get_template("admin/users_button_charge") . "\";");
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => "Edytuj {$row['username']}"
					));
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['username']}"
					));
				} else
					$button_charge = $button_delete = $button_edit = "";

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/users_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody)) {
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");
			}

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/users_thead") . "\";");

			// Pobranie struktury tabeli
			$title = $lang['users'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "groups":
			if (!get_privilages("view_groups")) {
				$output = $lang['no_privilages'];
				break;
			}

			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;

				if (get_privilages("manage_groups")) {
					// Pobranie przycisku edycji
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => "Edytuj {$row['name']}"
					));
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['name']}"
					));
				} else
					$button_delete = $button_edit = "";

				$row['name'] = htmlspecialchars($row['name']);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/groups_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/groups_thead") . "\";");

			if (get_privilages("manage_groups")) {
				// Pobranie przycisku dodającego taryfę
				$button = array(
					'id' => "button_add_group",
					'value' => $lang['add_group']);
				eval("\$buttons = \"" . get_template("admin/button") . "\";");
			}

			// Pobranie struktury tabeli
			$title = $lang['groups'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "income":
			if (!get_privilages("view_income")) {
				$output = $lang['no_privilages'];
				break;
			}

			$G_MONTH = isset($_GET['month']) ? $_GET['month'] : date("m");
			$G_YEAR = isset($_GET['year']) ? $_GET['year'] : date("Y");

			$table_row = "";
			// Uzyskanie wszystkich serwerów
			foreach ($heart->get_servers() as $id => $server) {
				$obejcts_ids[] = $id;
				$table_row .= create_dom_element("td", $server['name']);
			}
			$obejcts_ids[] = 0;

			$result = $db->query($db->prepare(
				"SELECT t.income, t.timestamp, t.server " .
				"FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.free = '0' AND IFNULL(t.income,'') != '' AND t.payment != 'wallet' AND t.timestamp LIKE '%s-%s-%%' " .
				"ORDER BY t.timestamp ASC",
				array($G_YEAR, $G_MONTH)
			));

			// Sumujemy dochód po dacie (z dokładnością do dnia) i po serwerze
			$data = array();
			while ($row = $db->fetch_array_assoc($result)) {
				$temp = explode(" ", $row['timestamp']);

				$data[$temp[0]][in_array($row['server'], $obejcts_ids) ? $row['server'] : 0] += $row['income'];
			}

			// Dodanie wyboru miesiąca
			$months = "";
			for ($i = 1; $i <= 12; $i++) {
				$months .= create_dom_element("option", $lang['months'][$i], array(
					'value' => str_pad($i, 2, 0, STR_PAD_LEFT),
					'selected' => $G_MONTH == $i ? "selected" : ""
				));
			}

			// Dodanie wyboru roku
			$years = "";
			for ($i = 2014; $i <= intval(date("Y")); $i++) {
				$years .= create_dom_element("option", $i, array(
					'value' => $i,
					'selected' => $G_YEAR == $i ? "selected" : ""
				));
			}
			eval("\$buttons = \"" . get_template("admin/income_button") . "\";");

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/income_thead") . "\";");

			//
			// Pobranie danych do tabeli

			// Pobieramy ilość dni w danym miesiącu
			$num = cal_days_in_month(CAL_GREGORIAN, $G_MONTH, $G_YEAR);

			$servers_incomes = array();
			// Lecimy pętla po każdym dniu
			for ($i = 1; $i <= $num; ++$i) {
				// Tworzymy wygląd daty
				$date = $G_YEAR . "-" . str_pad($G_MONTH, 2, 0, STR_PAD_LEFT) . "-" . str_pad($i, 2, 0, STR_PAD_LEFT);

				// Jeżeli jest to dzień z przyszłości
				if ($date > date("Y-m-d"))
					continue;

				// Zerujemy dochód w danym dniu na danym serwerze
				$day_income = 0;
				$table_row = "";

				// Lecimy po każdym obiekcie, niezależnie, czy zarobiliśmy na nim czy nie
				foreach ($obejcts_ids as $object_id) {
					$income = $data[$date][$object_id];
					$day_income += $income;
					$servers_incomes[$object_id] += $income;
					$table_row .= create_dom_element("td", number_format($income, 2));
				}

				// Zaokraglenie do dowch miejsc po przecinku zarobku w danym dniu
				$day_income = number_format($day_income, 2);

				eval("\$tbody .= \"" . get_template("admin/income_trow") . "\";");
			}

			// Pobranie podliczenia tabeli
			$table_row = "";
			$total_income = 0;
			// Lecimy po wszystkich obiektach na których zarobiliśmy kasę
			foreach ($servers_incomes as $server_income) {
				$total_income += $server_income; // Całk przychód
				$table_row .= create_dom_element("td", number_format($server_income, 2));
			}

			// Jeżeli coś się policzyło, są jakieś dane
			if (isset($tbody)) {
				$total_income = number_format($total_income, 2);
				eval("\$tbody .= \"" . get_template("admin/income_trow2") . "\";");
			} else { // Brak danych
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");
			}

			// Pobranie wygladu strony
			$title = $lang['income'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "sms_codes":
			if (!get_privilages("view_sms_codes")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Pobranie kodów SMS
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM `" . TABLE_PREFIX . "sms_codes` " .
				"WHERE `free` = '1' " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Pobranie przycisku usuwania
				if (get_privilages("manage_sms_codes")) {
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['id']}"
					));
				} else
					$button_delete = "";

				// Zabezpieczanie danych
				$row['code'] = htmlspecialchars($row['code']);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/sms_codes_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody)) {
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");
			}

			if (get_privilages("manage_sms_codes")) {
				// Pobranie przycisku dodającego taryfę
				$button = array(
					'id' => "button_add_sms_code",
					'value' => $lang['add_sms_code']);
				eval("\$buttons = \"" . get_template("admin/button") . "\";");
			}

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/sms_codes_thead") . "\";");

			// Pobranie wygladu całej tabeli
			$title = $lang['sms_codes'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "logs":
			if (!get_privilages("view_logs")) {
				$output = $lang['no_privilages'];
				break;
			}

			// Wyszukujemy dane ktore spelniaja kryteria
			if (isset($_GET['search'])) {
				searchWhere(array("`id`", "`text`", "CAST(`timestamp` as CHAR)"), urldecode($_GET['search']), $where);
			}

			// Jezeli jest jakis where, to dodajemy WHERE
			if (strlen($where))
				$where = "WHERE {$where} ";

			// Pobranie logów
			$result = $db->query(
				"SELECT SQL_CALC_FOUND_ROWS * " .
				"FROM `" . TABLE_PREFIX . "logs` " .
				$where .
				"ORDER BY `id` DESC " .
				"LIMIT " . get_row_limit($G_PAGE)
			);
			$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

			$i = 0;
			$tbody = "";
			while ($row = $db->fetch_array_assoc($result)) {
				$i += 1;
				// Pobranie przycisku usuwania
				if (get_privilages("manage_logs")) {
					$button_delete = create_dom_element("img", "", array(
						'id' => "delete_row_{$i}",
						'src' => "images/bin.png",
						'title' => "Usuń {$row['id']}"
					));
				}
				else
					$button_delete = "";

				// Zabezpieczanie danych
				$row['text'] = htmlspecialchars($row['text']);

				// Pobranie danych do tabeli
				eval("\$tbody .= \"" . get_template("admin/logs_trow") . "\";");
			}

			// Nie ma zadnych danych do wyswietlenia
			if (!strlen($tbody))
				eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

			// Pole wyszukiwania
			$search_text = htmlspecialchars($_GET['search']);
			eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

			// Pobranie paginacji
			$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $_GET);
			if ($pagination) $tfoot_class = "display_tfoot";

			// Pobranie nagłówka tabeli
			eval("\$thead = \"" . get_template("admin/logs_thead") . "\";");

			// Pobranie wygladu całej tabeli
			$title = $lang['logs'];
			eval("\$output = \"" . get_template("admin/table_structure") . "\";");
			break;

		case "update_web":
			if (!get_privilages("update")) {
				$output = $lang['no_privilages'];
				break;
			}

			$newest_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=web"));
			$version = simplexml_load_file("http://www.sklep-sms.pl/version.php?action=get_version&type=web&version={$newest_version}", 'SimpleXMLElement', LIBXML_NOCDATA);
			$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type=web&version=" . VERSION));

			// Mamy najnowszą wersję
			if (!strlen($newest_version) || !strlen($next_version) || VERSION == $newest_version) {
				eval("\$output = \"" . get_template("admin/no_update") . "\";");
				break;
			}

			// Pobieramy dodatkowe informacje
			$additional_info = "";
			foreach ($version->extra_info->children() as $value) {
				$additional_info .= create_dom_element("li", $value);
			}
			if (strlen($additional_info))
				eval("\$additional_info = \"" . get_template("admin/update_additional_info") . "\";");

			// Pobieramy listę plików do wymiany
			$files = "";
			foreach ($version->files->children() as $value) {
				$files .= create_dom_element("li", $value);
			}

			// Pobieramy listę zmian
			$changelog = "";
			foreach ($version->changelog->children() as $value) {
				$changelog .= create_dom_element("li", $value);
			}

			// Pobieramy plik najnowszej wersji full
			$file_data['type'] = "full";
			$file_data['platform'] = "web";
			$file_data['version'] = $newest_version;
			eval("\$shop_files['newest_full'] = \"" . get_template("admin/update_file") . "\";");

			// Pobieramy plik kolejnej wersji update
			if ($next_version) {
				$file_data['type'] = "update";
				$file_data['platform'] = "web";
				$file_data['version'] = $next_version;
				eval("\$shop_files['next_update'] = \"" . get_template("admin/update_file") . "\";");
			} else {
				$shop_files['next_update'] = $next_version = $lang['lack'];
			}

			// Pobranie wyglądu całej strony
			$title = $lang['update_web'];
			eval("\$output = \"" . get_template("admin/update_web") . "\";");
			break;

		case "update_server":
			if (!get_privilages("update")) {
				$output = $lang['no_privilages'];
				break;
			}

			$newest_versions = json_decode(trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=engines")), true);

			$version_blocks = "";
			foreach ($heart->get_servers() as $server) {
				$engine = "engine_{$server['type']}";
				// Mamy najnowszą wersję
				if ($server['version'] == $newest_versions[$engine])
					continue;

				$name = htmlspecialchars($server['name']);
				$current_version = $server['version'];
				$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type={$engine}&version={$server['version']}"));
				$newest_version = $newest_versions[$engine];

				// Nie ma kolejnej wersji
				if (!strlen($next_version))
					continue;

				// Pobieramy informacje o danym serwerze, jego obecnej wersji i nastepnej wersji
				eval("\$version_blocks .= \"" . get_template("admin/update_version_block") . "\";");

				// Pobieramy plik kolejnej wersji update
				$file_data['type'] = "update";
				$file_data['platform'] = $engine;
				$file_data['version'] = $next_version;
				eval("\$next_package = \"" . get_template("admin/update_file") . "\";");

				// Pobieramy plik najnowszej wersji full
				$file_data['type'] = "full";
				$file_data['platform'] = $engine;
				$file_data['version'] = $newest_version;
				eval("\$newest_package = \"" . get_template("admin/update_file") . "\";");

				eval("\$servers_versions .= \"" . get_template("admin/update_server_version") . "\";");
			}

			// Brak aktualizacji
			if (!strlen($version_blocks)) {
				eval("\$output = \"" . get_template("admin/no_update") . "\";");
				break;
			}

			// Pobranie wyglądu całej strony
			$title = $lang['update_servers'];
			eval("\$output = \"" . get_template("admin/update_server") . "\";");
			break;
	}

	if ($withenvelope)
		$output = create_dom_element("div", $output, array(
			'id' => $element,
			'class' => if_isset($class, "")
		));

	return $separateclass ? array('content' => $output, 'class' => $class) : $output;
}