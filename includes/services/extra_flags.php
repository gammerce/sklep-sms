<?php

$heart->register_service_module("extra_flags", "Dodatkowe Uprawnienia / Flagi", "ServiceExtraFlags", "ServiceExtraFlagsSimple");

class ServiceExtraFlagsSimple extends Service implements IServiceCreateNew
{

	const MODULE_ID = "extra_flags";

	public $info = array(
		'available_on_servers' => true
	);

	public function service_extra_fields()
	{
		global $heart;

		// WEB
		if ($this->show_on_web()) $web_sel_yes = "selected";
		else $web_sel_no = "selected";

		// Nick, IP, SID
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			$types .= create_dom_element("option", get_type_name($option_id), array(
				'value' => $option_id,
				'selected' => $this->service !== NULL && $this->service['types'] & $option_id ? "selected" : ""
			));

		// Pobieramy flagi, jeżeli service nie jest puste
		// czyli kiedy edytujemy, a nie dodajemy usługę
		if ($this->service !== NULL)
			$flags = $this->service['flags_hsafe'];

		eval("\$output = \"" . get_template("services/extra_flags/extra_fields", 0, 1, 0) . "\";");

		return $output;
	}

	public function manage_service_pre($data)
	{
		global $lang;

		// Web
		if (!in_array($data['web'], array("1", "0"))) {
			$output['web'] = $lang['only_yes_no'];
		}

		// Flagi
		if (!strlen($data['flags']))
			$output['flags'] = "Nie wprowadzono ani jednej flagi.<br />";
		else if (strlen($data['flags']) > 25)
			$output['flags'] = "Wprowadzono zbyt dużo flag. Maksymalna ilość to 25.<br />";
		else if (implode('', array_unique(str_split($data['flags']))) != $data['flags'])
			$output['flags'] = "Niektóre flagi są wpisane więcej niż jeden raz.<br />";

		// Typy
		if (empty($data['type']))
			$output['type[]'] = "Nie wybrano żadnego typu.<br />";

		// Sprawdzamy, czy typy są prawidłowe
		foreach ($data['type'] as $type)
			if (!($type & (TYPE_NICK | TYPE_IP | TYPE_SID))) {
				$output['type[]'] .= "Wybrano błędny typ.<br />";
				break;
			}

		return $output;
	}

	public function manage_service_post($data)
	{
		global $db, $settings;

		$output = array();

		// Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
		$types = 0;
		foreach ($data['type'] as $type) {
			$types |= $type;
		}

		$extra_data = $this->service['data'];
		$extra_data['web'] = $data['web'];

		$output['query_set'] = $db->prepare(
			", `types`='%d', `flags`='%s', `data`='%s'",
			array($types, $data['flags'], json_encode($extra_data))
		);

		// Tworzymy plik z opisem usługi
		$file = SCRIPT_ROOT . "themes/{$settings['theme']}/services/" . escape_filename($data['id']) . "_desc.html";
		if (!file_exists($file)) {
			file_put_contents($file, "");

			// Dodajemy uprawnienia
			chmod($file, 0777);

			// Sprawdzamy czy uprawnienia się dodały
			if (substr(sprintf('%o', fileperms($file)), -4) != "0777")
				json_output("not_created", "Plik z opisem usługi nie został prawidłowo utworzony.<br />Prawdopodobnie folder <strong>themes/{$settings['theme']}/services/</strong> nie ma uprawnień do zapisu.", 0);
		}

		if ($data['action'] == "add_service")
			$db->query($db->prepare(
				"ALTER TABLE `" . TABLE_PREFIX . "servers` " .
				"ADD  `%s` TINYINT( 1 ) NOT NULL DEFAULT '0'",
				array($data['id'])
			));
		else
			$db->query($db->prepare(
				"ALTER TABLE `" . TABLE_PREFIX . "servers` " .
				"CHANGE  `%s`  `%s` TINYINT( 1 ) NOT NULL DEFAULT '0'",
				array($data['id2'], $data['id'])
			));

		return $output;
	}

}

class ServiceExtraFlags extends ServiceExtraFlagsSimple implements IServicePurchase, IServicePurchaseWeb, IServiceAdminManageUserService,
	IServiceExecuteAction, IServiceUserEdit, IServiceTakeOver
{

	function __construct($service)
	{
		global $settings, $scripts, $stylesheets, $G_PID;

		// Wywolujemy konstruktor klasy ktora rozszerzamy
		parent::__construct($service);

		$this->service['flags_hsafe'] = htmlspecialchars($this->service['flags']);

		// Dodajemy do strony skrypt js
		$scripts[] = "{$settings['shop_url_slash']}jscripts/services/extra_flags.js?version=" . VERSION;
		if ($G_PID == "take_over_service")
			$scripts[] = "{$settings['shop_url_slash']}jscripts/services/extra_flags_take_over.js?version=" . VERSION;
		// Dodajemy szablon css
		$stylesheets[] = "{$settings['shop_url_slash']}styles/services/extra_flags.css?version=" . VERSION;
	}

	public function get_form($form, $data)
	{
		if ($form == "admin_add_user_service")
			return $this->form_admin_add_user_service();
		else if ($form == "admin_edit_user_service")
			return $this->form_admin_edit_user_service($data);
		else if ($form == "user_edit_user_service")
			return $this->form_user_edit_user_service($data);
	}

	public function form_purchase_service()
	{
		global $heart, $lang, $settings, $user;

		// Generujemy typy usługi
		for ($i = 0, $value = 1; $i < 3; $value = 1 << ++$i)
			if ($this->service['types'] & $value) {
				$type = get_type_name($value);
				eval("\$types .= \"" . get_template("services/extra_flags/service_type") . "\";");
			}

		// Pobieranie serwerów na których można zakupić daną usługę
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			// Usługi nie mozna kupic na tym serwerze
			if ($row[$this->service['id']] != "1")
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		eval("\$output = \"" . get_template("services/extra_flags/purchase_form") . "\";");

		return $output;
	}

	public function validate_purchase_form($data)
	{
		global $user;

		// Wyłuskujemy taryfę
		$value = explode(';', $data['value']);

		// Pobieramy auth_data
		$auth_data = $this->get_auth_data($data);

		return $this->validate_purchase_data(array(
			'user' => array(
				'uid' => $user['uid'],
				'email' => trim($data['email'])
			),
			'order' => array(
				'server' => $data['server'],
				'type' => $data['type'],
				'auth_data' => trim($auth_data),
				'password' => $data['password'],
				'passwordr' => $data['password_repeat']
			),
			'tariff' => $value[2]
		));
	}

	/**
	 * order
	 *    server - serwer na który ma być wykupiona usługa
	 *    type - TYPE_NICK / TYPE_IP / TYPE_SID
	 *    auth_data - dane rozpoznawcze gracza
	 *    password - hasło do usługi
	 *    passwordr - powtórzenie hasła
	 *
	 * (non-PHPdoc)
	 * @see Service::validate_purchase_data()
	 */
	public function validate_purchase_data($data)
	{
		global $heart, $db, $lang, $settings;

		$warnings = array();

		// Serwer
		if (!strlen($data['order']['server']))
			$warnings['server'] .= $lang['must_choose_server'] . "<br />";
		else {
			// Sprawdzanie czy serwer o danym id istnieje w bazie
			$server = $heart->get_server($data['order']['server']);
			if (!$server[$this->service['id']])
				$warnings['server'] .= $lang['chosen_incorrect_server'] . "<br />";
		}

		// Wartość usługi
		if (!$data['tariff'])
			$warnings['value'] .= $lang['must_choose_amount'] . "<br />";
		else {
			// Wyszukiwanie usługi o konkretnej cenie
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
				array($this->service['id'], $data['tariff'], $server['id'])
			));

			if (!$db->num_rows($result)) // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
				return array(
					'status' => "no_option",
					'text' => $lang['service_not_affordable'],
					'positive' => false
				);
			else
				$price = $db->fetch_array_assoc($result);
		}

		// Typ usługi
		// Mogą być tylko 3 rodzaje typu
		if (!($data['order']['type'] & (TYPE_NICK | TYPE_IP | TYPE_SID)))
			$warnings['type'] .= $lang['must_choose_type'] . "<br />";
		else if (!($this->service['types'] & $data['order']['type']))
			$warnings['type'] .= $lang['chosen_incorrect_type'] . "<br />";
		else if ($data['order']['type'] & (TYPE_NICK | TYPE_IP)) {
			// Nick
			if ($data['order']['type'] == TYPE_NICK) {
				if ($warning = check_for_warnings("nick", $data['order']['auth_data']))
					$warnings['nick'] = $warning;

				// Sprawdzanie czy istnieje już taka usługa
				$query = $db->prepare(
					"SELECT `password` FROM `" . TABLE_PREFIX . "players_services` " .
					"WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
					array(TYPE_NICK, $data['order']['auth_data'], $server['id'])
				);
			} // IP
			else if ($data['order']['type'] == TYPE_IP) {
				if ($warning = check_for_warnings("ip", $data['order']['auth_data']))
					$warnings['ip'] = $warning;

				// Sprawdzanie czy istnieje już taka usługa
				$query = $db->prepare(
					"SELECT `password` FROM `" . TABLE_PREFIX . "players_services` " .
					"WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
					array(TYPE_NICK, $data['order']['auth_data'], $server['id'])
				);
			}

			// Hasło
			if ($warning = check_for_warnings("password", $data['order']['password']))
				$warnings['password'] = $warning;
			if ($data['order']['password'] != $data['order']['passwordr'])
				$warnings['password_repeat'] .= $lang['passwords_not_match'] . "<br />";

			// Sprawdzanie czy istnieje już taka usługa
			if ($temp_password = $db->get_column($query, 'password'))
				// TODO: Usunąć md5 w przyszłości
				if ($temp_password != $data['order']['password'] && $temp_password != md5($data['order']['password']))
					$warnings['password'] .= $lang['existing_service_has_different_password'] . "<br />";

			unset($temp_password);
		} // SteamID
		else // $data['order']['type'] == TYPE_SID
			if ($warning = check_for_warnings("sid", $data['order']['auth_data']))
				$warnings['sid'] = $warning;

		// E-mail
		if (strpos($data['user']['platform'], "engine") !== 0 && $warning = check_for_warnings("email", $data['user']['email']))
			$warnings['email'] = $warning;

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang['form_wrong_filled'],
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);

		//
		// Wszystko przebiegło pomyślnie, zwracamy o tym info

		// Pobieramy koszt usługi dla przelewu / paypal / portfela
		$cost_transfer = $heart->get_tariff_provision($data['tariff']);

		$purchase_data = array(
			'service' => $this->service['id'],
			'order' => array(
				'server' => $data['order']['server'],
				'type' => $data['order']['type'],
				'auth_data' => $data['order']['auth_data'],
				'password' => $data['order']['password'],
				'amount' => $price['amount'],
				'forever' => $price['amount'] == -1 ? true : false
			),
			'user' => $data['user'],
			'tariff' => $data['tariff'],
			'cost_transfer' => $cost_transfer,
			'sms_service' => $server['sms_service']
		);

		return array(
			'status' => "validated",
			'text' => $lang['purchase_form_validated'],
			'positive' => true,
			'purchase_data' => $purchase_data
		);
	}

	//
	// Szczegóły zamówienia
	public function order_details($data)
	{
		global $heart, $lang;

		$server = $heart->get_server($data['order']['server']);
		$data['order']['type_name'] = get_type_name2($data['order']['type']);
		if ($data['order']['password'])
			$password = "<strong>{$lang['password']}</strong>: " . htmlspecialchars($data['order']['password']) . "<br />";
		$data['order']['email'] = $data['order']['email'] ? htmlspecialchars($data['order']['email']) : $lang['none'];
		$data['order']['auth_data'] = htmlspecialchars($data['order']['auth_data']);
		$amount = $data['order']['amount'] != -1 ? "{$data['order']['amount']} {$this->service['tag']}" : $lang['forever'];

		eval("\$output = \"" . get_template("services/extra_flags/order_details", 0, 1, 0) . "\";");
		return $output;
	}

	public function purchase($data)
	{
		// Dodanie graczowi odpowiednich flag
		$this->add_player_flags($data['user']['uid'], $data['order']['type'], $data['order']['auth_data'], $data['order']['password'],
			$data['order']['amount'], $data['order']['server'], $data['order']['forever']);

		// Dodanie informacji o zakupie usługi
		return add_bought_service_info($data['user']['uid'], $data['user']['username'], $data['user']['ip'], $data['transaction']['method'],
			$data['transaction']['payment_id'], $this->service['id'], $data['order']['server'], $data['order']['amount'], $data['order']['auth_data'],
			$data['user']['email'], array('type' => $data['order']['type'], 'password' => $data['order']['password'])
		);
	}

	private function add_player_flags($uid, $type, $auth_data, $password, $days, $server, $forever = false)
	{
		global $db;

		$auth_data = trim($auth_data);

		// Usunięcie przestarzałych usług gracza
		delete_players_old_services();

		// Dodajemy usługę gracza do listy usług
		// Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "players_services` (`uid`, `server`, `service`, `type`, `auth_data`, `password`, `expire`) " .
			"VALUES ('%d','%d','%s','%d','%s','%s',IF('%d' = '1', '-1', UNIX_TIMESTAMP()+'%d')) " .
			"ON DUPLICATE KEY UPDATE `uid` = '%d', `password` = '%s', `expire` = IF('%d' = '1', '-1', `expire`+'%d')",
			array($uid, $server, $this->service['id'], $type, $auth_data, $password, $forever, $days * 24 * 60 * 60, $uid, $password, $forever, $days * 24 * 60 * 60)
		));

		// Ustawiamy jednakowe hasła dla wszystkich usług tego gracza na tym serwerze
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "players_services` " .
			"SET `password` = '%s' " .
			"WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
			array($password, $server, $type, $auth_data)
		));

		// Przeliczamy flagi gracza, ponieważ dodaliśmy nową usługę
		$this->recalculate_player_flags($server, $type, $auth_data);
	}

	private function recalculate_player_flags($server, $type, $auth_data)
	{
		global $heart, $db;

		// Musi byc podany typ, bo inaczej nam wywali wszystkie usługi bez typu
		// Bez serwera oraz auth_data, skrypt po prostu nic nie zrobi
		if (!$type)
			return;

		// Usuwanie danych z bazy players_flags
		// Ponieważ za chwilę będziemy je tworzyć na nowo
		$db->query($db->prepare(
			"DELETE FROM `" . TABLE_PREFIX . "players_flags` " .
			"WHERE `server`='%d' AND `type`='%d' AND `auth_data`='%s'",
			array($server, $type, $auth_data)
		));

		// Pobieranie wszystkich usług na konkretne dane
		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `server`='%d' AND `type`='%d' AND `auth_data`='%s' AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = '-1' )",
			array($server, $type, $auth_data)
		));

		// Wyliczanie za jaki czas dana flaga ma wygasnąć
		$flags = array();
		$password = "";
		while ($row = $db->fetch_array_assoc($result)) {
			// Pobranie hasła, bierzemy je tylko raz na początku
			$password = $password ? $password : $row['password'];

			$service = $heart->get_service($row['service']);
			for ($i = 0; $i < strlen($service['flags']); ++$i) {
				// Bierzemy maksa, ponieważ inaczej robią się problemy.
				// A tak to jak wygaśnie jakaś usługa, to wykona się cron, usunie ją i przeliczy flagi jeszcze raz
				// I znowu weźmie maksa
				// Czyli stan w tabeli players flags nie jest do końca odzwierciedleniem rzeczywistości :)
				$flags[$service['flags'][$i]] = $this->max_minus($flags[$service['flags'][$i]], $row['expire']);
			}
		}

		// Formowanie flag do zapytania
		$set = "";
		foreach ($flags as $flag => $amount)
			$set .= $db->prepare(", `%s`='%d'", array($flag, $amount));

		// Dodanie flag
		if (strlen($set))
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "players_flags` " .
				"SET `server`='%d', `type`='%d', `auth_data`='%s', `password`='%s'{$set}",
				array($server, $type, $auth_data, $password)
			));
	}

	//
	// Funkcja zwraca informacje o zakupie usługi
	public function purchase_info($action, $data)
	{
		global $heart, $settings, $lang;

		$data['extra_data'] = json_decode($data['extra_data'], true);
		$data['extra_data']['type_name'] = get_type_name2($data['extra_data']['type']);
		if (strlen($data['extra_data']['password']))
			$password = "<strong>{$lang['password']}</strong>: " . htmlspecialchars($data['extra_data']['password']) . "<br />";
		$amount = $data['amount'] != -1 ? "{$data['amount']} {$this->service['tag']}" : $lang['forever'];
		$data['auth_data'] = htmlspecialchars($data['auth_data']);
		$data['extra_data']['password'] = htmlspecialchars($data['extra_data']['password']);
		$data['email'] = htmlspecialchars($data['email']);
		$data['cost'] = number_format($data['cost'], 2);
		$data['income'] = number_format($data['income'], 2);

		if ($data['payment'] == "sms") {
			$data['sms_code'] = htmlspecialchars($data['sms_code']);
			$data['sms_text'] = htmlspecialchars($data['sms_text']);
			$data['sms_number'] = htmlspecialchars($data['sms_number']);
		}

		$server = $heart->get_server($data['server']);

		if ($data['extra_data']['type'] & (TYPE_NICK | TYPE_IP))
			$setinfo = newsprintf($lang['type_setinfo'], htmlspecialchars($data['extra_data']['password']));

		if ($action == "email")
			eval("\$output = \"" . get_template("services/extra_flags/purchase_info_web", false, true, false) . "\";");
		else if ($action == "web")
			eval("\$output = \"" . get_template("services/extra_flags/purchase_info_web", false, true, false) . "\";");
		else if ($action == "payment_log")
			return array(
				'text' => $output = newsprintf($lang['service_was_bought'], $this->service['name'], $server['name']),
				'class' => "outcome"
			);

		return $output;
	}

	// ----------------------------------------------------------------------------------
	// ### Zarządzanie usługami

	//
	// Funkcja wywolywana podczas usuwania uslugi
	public function delete_service($service_id)
	{
		global $db;

		$db->query($db->prepare(
			"ALTER TABLE `" . TABLE_PREFIX . "servers` " .
			"DROP `%s`",
			array($service_id)
		));
	}

	// ----------------------------------------------------------------------------------
	// ### Zarządzanie usługami użytkowników przez admina

	//
	// Funkcja zwraca formularz dodawania usług graczom przez PA
	// $output:
	// 	text - tekst wstawiany do action boxa
	// 	scripts - skrypty które dorzucamy do strony
	//
	private function form_admin_add_user_service()
	{
		global $heart, $settings, $lang;

		// Pobieramy listę typów usługi, (1<<2) ostatni typ
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			if ($this->service['types'] & $option_id)
				$types .= create_dom_element("option", get_type_name($option_id), array(
					'value' => $option_id
				));

		// Pobieramy listę serwerów
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			if (!$row[$this->service['id']])
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		eval("\$output['text'] = \"" . get_template("services/extra_flags/admin_add_user_service", 0, 1, 0) . "\";");

		$output['scripts'] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/services/extra_flags_add_user_service.js?version=" . VERSION . "\"></script>";

		return json_encode($output);
	}

	//
	// Funkcja dodawania usługi przez PA
	//
	public function admin_add_user_service($data)
	{
		global $heart, $lang, $user;

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Sprawdzamy hasło, jeżeli podano nick albo ip
		if ($data['type'] & (TYPE_NICK | TYPE_IP) && $warning = check_for_warnings("password", $data['password']))
			$warnings['password'] = $warning;

		// Amount
		if (!$data['forever']) {
			if ($warning = check_for_warnings("number", $data['amount'])) {
				$warnings['amount'] = $warning;
			} else if ($data['amount'] < 0) {
				$warnings['amount'] .= "Ilość dni musi być nieujemna.<br />";
			}
		}

		// E-mail
		if (strlen($data['email']) && $warning = check_for_warnings("email", $data['email']))
			$warnings['email'] = $warning;

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		// Pobieramy dane o użytkowniku na które jego wykupiona usługa
		$user2 = $heart->get_user($data['uid']);

		//
		// Dodajemy usługę

		// Dodawanie informacji o płatności
		$payment_id = pay_by_admin($user);

		// Dokonujemy zakupu usługi
		$bought_service_id = $this->purchase(array(
			'user' => array(
				'uid' => $data['uid'],
				'name' => $user2['username'],
				'ip' => $user2['ip'],
				'email' => $data['email']
			),
			'transaction' => array(
				'method' => "admin",
				'payment_id' => $payment_id
			),
			'order' => array(
				'server' => $data['server'],
				'type' => $data['type'],
				'auth_data' => trim($data['auth_data']),
				'password' => $data['password'],
				'amount' => $data['amount'],
				'forever' => (boolean)$data['forever']
			)
		));

		log_info("Admin {$user['username']}({$user['uid']}) dodał graczowi usługę. ID zakupu: {$bought_service_id}");

		return array(
			'status' => "added",
			'text' => "Prawidłowo dodano usługę graczowi.",
			'positive' => true
		);
	}

	//
	// Funkcja zwraca formularz edycji usługi użytkownika przez PA
	//
	private function form_admin_edit_user_service($player_service)
	{
		global $heart, $settings, $lang;

		// Pobranie usług
		$services = "";
		foreach ($heart->get_services() as $id => $row) {
			if (($service_module = $heart->get_service_module_s($row['module'])) === NULL)
				continue;
			// Usługę możemy zmienić tylko na taka, która korzysta z tego samego modułu.
			// Inaczej to nie ma sensu, lepiej ją usunąć i dodać nową
			if ($this::MODULE_ID != $service_module::MODULE_ID)
				continue;

			$services .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $player_service['service'] == $row['id'] ? "selected" : ""
			));
		}

		// Dodajemy typ uslugi, (1<<2) ostatni typ
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			if ($this->service['types'] & $option_id)
				$types .= create_dom_element("option", get_type_name($option_id), array(
					'value' => $option_id,
					'selected' => $option_id == $player_service['type'] ? "selected" : ""
				));

		if ($player_service['type'] == TYPE_NICK)
			$nick = htmlspecialchars($player_service['auth_data']);
		else if ($player_service['type'] == TYPE_IP)
			$ip = htmlspecialchars($player_service['auth_data']);
		else if ($player_service['type'] == TYPE_SID)
			$sid = htmlspecialchars($player_service['auth_data']);

		// Pobranie serwerów
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			// Usługi którą edytujemy, nie można zakupić na serwerze
			if (!$row[$this->service['id']])
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $player_service['server'] == $row['id'] ? "selected" : ""
			));
		}

		// Pobranie hasła
		if (strlen($player_service['password']))
			$password = "********";

		// Zamiana daty
		if ($player_service['expire'] == -1) {
			$checked = "checked";
			$disabled = "disabled";
			$player_service['expire'] = "";
		} else
			$player_service['expire'] = date($settings['date_format'], $player_service['expire']);

		eval("\$output = \"" . get_template("services/extra_flags/admin_edit_user_service", 0, 1, 0) . "\";");

		return $output;
	}

	//
	// Funkcja edytowania usługi przez admina z PA
	//
	public function admin_edit_user_service($data, $user_service)
	{
		global $heart, $db, $lang, $user;

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Expire
		if (!$data['forever'] && ($data['expire'] = strtotime($data['expire'])) === FALSE)
			$warnings['expire'] = "Błędny format daty.<br />";

		// Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
		if (!strlen($data['password']) && $data['type'] & (TYPE_NICK | TYPE_IP) && !strlen($user_service['password']))
			$warnings['password'] = $lang['field_no_empty'];

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		//
		// Aktualizujemy usługę
		$edit_return = $this->edit_user_service($user_service, $data);

		if ($edit_return['status'] == "edited")
			log_info("Admin {$user['username']}({$user['uid']}) edytował usługę gracza. ID: {$user_service['id']}");

		return $edit_return;
	}

	//
	// Weryfikacja danych przy dodawaniu i przy edycji usługi gracza
	// Zebrane w jednej funkcji, aby nie mnożyć kodu
	//
	private function verify_user_service_data($data, $warnings, $server = true)
	{
		global $heart, $lang;

		// ID użytkownika
		if ($data['uid']) {
			if ($warning = check_for_warnings("uid", $data['uid']))
				$warnings['uid'] .= $warning;
			else {
				$user2 = $heart->get_user($data['uid']);
				if (!isset($user2['uid']))
					$warnings['uid'] .= "Podane ID użytkownika nie jest przypisane do żadnego konta.<br />";
			}
		}

		// Typ usługi
		// Mogą być tylko 3 rodzaje typu
		if (!($data['type'] & (TYPE_NICK | TYPE_IP | TYPE_SID)))
			$warnings['type'] .= "Musisz wybrać typ usługi<br />";
		else if (!($this->service['types'] & $data['type']))
			$warnings['type'] .= "Wybrano niedozwolony typ zakupu.<br />";
		else if ($data['type'] & (TYPE_NICK | TYPE_IP)) {
			// Nick
			if ($data['type'] == TYPE_NICK && $warning = check_for_warnings("nick", $data['auth_data']))
				$warnings['nick'] .= $warning;
			// IP
			else if ($data['type'] == TYPE_IP && $warning = check_for_warnings("ip", $data['auth_data']))
				$warnings['ip'] .= $warning;

			// Hasło
			if (strlen($data['password']) && $warning = check_for_warnings("password", $data['password']))
				$warnings['password'] .= $warning;
		} // SteamID
		else if ($warning = check_for_warnings("sid", $data['auth_data']))
			$warnings['sid'] .= $warning;

		// Server
		if ($server) {
			if (!strlen($data['server']))
				$warnings['server'] .= "Musisz wybrać serwer na który chcesz dodać daną usługę.<br />";
			// Wyszukiwanie serwera o danym id
			else if (($server = $heart->get_server($data['server'])) === NULL)
				$warnings['server'] .= "Brak serwera o takim ID. Coś tu ktoś namieszał.<br />";
		}

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang['form_wrong_filled'],
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);
	}

	public function delete_player_service_post($player_service)
	{
		// Odśwież flagi gracza
		$this->recalculate_player_flags($player_service['server'], $player_service['type'], $player_service['auth_data']);
	}

	// ----------------------------------------------------------------------------------
	// ### Edytowanie usług przez użytkownika

	private function form_user_edit_user_service($player_service)
	{
		global $heart, $settings, $lang;

		// Dodajemy typ uslugi, (1<<2) ostatni typ
		$service_info = array();
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i) {
			// Kiedy dana usługa nie wspiera danego typu i wykupiona usługa nie ma tego typu
			if (!($this->service['types'] & $option_id) && $option_id != $player_service['type'])
				continue;

			$service_info['types'] .= create_dom_element("option", get_type_name($option_id), array(
				'value' => $option_id,
				'selected' => $option_id == $player_service['type'] ? "selected" : ""
			));

			if ($option_id == $player_service['type']) {
				if ($option_id == TYPE_NICK)
					$service_info['player_nick'] = htmlspecialchars($player_service['auth_data']);
				else if ($option_id == TYPE_IP)
					$service_info['player_ip'] = htmlspecialchars($player_service['auth_data']);
				else if ($option_id == TYPE_SID)
					$service_info['player_sid'] = htmlspecialchars($player_service['auth_data']);
				else
					$service_info['player_ip'] = htmlspecialchars($player_service['auth_data']);
			}
		}

		// Hasło
		if (strlen($player_service['password']) && $player_service['password'] != md5(""))
			$service_info['password'] = "********";

		// Serwer
		$temp_server = $heart->get_server($player_service['server']);
		$service_info['server'] = $temp_server['name'];
		unset($temp_server);

		// Wygasa
		$service_info['expire'] = $player_service['expire'] == -1 ? $lang['never'] : date($settings['date_format'], $player_service['expire']);

		// Usługa
		$service_info['service'] = $this->service['name'];

		eval("\$output .= \"" . get_template("services/extra_flags/user_edit_service") . "\";");

		return $output;
	}

	public function my_service_info($data, $button_edit)
	{
		global $heart, $settings, $lang, $scripts;

		$service_info['expire'] = $data['expire'] == -1 ? $lang['never'] : date($settings['date_format'], $data['expire']);
		$temp_server = $heart->get_server($data['server']);
		$service_info['server'] = $temp_server['name'];
		$service_info['service'] = $this->service['name'];
		$service_info['type'] = get_type_name2($data['type']);
		$service_info['auth_data'] = htmlspecialchars($data['auth_data']);
		unset($temp_server);

		// Dodajemy skrypty
		$scripts[] = "{$settings['shop_url_slash']}jscripts/services/extra_flags.js?version=" . VERSION;
		$scripts[] = "{$settings['shop_url_slash']}jscripts/services/extra_flags_user_edit_service.js?version=" . VERSION;

		$module_id = $this::MODULE_ID;
		eval("\$output = \"" . get_template("services/extra_flags/my_service") . "\";");

		return $output;
	}

	public function user_edit_user_service($data, $user_service)
	{
		global $lang, $user;

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
		if (!strlen($data['password']) && $data['type'] & (TYPE_NICK | TYPE_IP) && !strlen($user_service['password']))
			$warnings['password'] = $lang['field_no_empty'];

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings, false);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		//
		// Aktualizujemy usługę

		$edit_return = $this->edit_user_service($user_service, array(
			'type' => $data['type'],
			'auth_data' => $data['auth_data'],
			'password' => $data['password']
		));

		if ($edit_return['status'] == "edited")
			log_info("Użytkownik {$user['username']}({$user['uid']}) wyedytował swoją usługę. ID: {$user_service['id']}");

		return $edit_return;
	}

	// ----------------------------------------------------------------------------------
	// ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

	private function edit_user_service($user_service, $data)
	{
		global $db, $lang;

		// Dodanie hasła do zapytania
		if (strlen($data['password']))
			$set[] = $db->prepare("`password`='%s'", array($data['password']));

		// Dodajemy uid do zapytania
		if (isset($data['uid']))
			$set[] = $db->prepare("`uid`='%d'", array($data['uid']));

		// Sprawdzenie czy nie ma już takiej usługi
		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND `id` != '%d'",
			array($this->service['id'], if_isset($data['server'], $user_service['server']), if_isset($data['type'], $user_service['type']),
				if_isset($data['auth_data'], $user_service['auth_data']), $user_service['id'])
		));

		// Jeżeli istnieje usługa o identycznych danych jak te, na które będziemy zmieniać obecną usługę
		if ($db->num_rows($result)) {
			// Pobieramy tę drugą usługę
			$user_service2 = $db->fetch_array_assoc($result);

			if (!isset($data['uid']) && $user_service['uid'] != $user_service2['uid'])
				return array(
					'status' => "service_exists",
					'text' => $lang['service_isnt_yours'],
					'positive' => false
				);

			// Usuwamy opcję którą aktualizujemy
			$db->query($db->prepare(
				"DELETE FROM `" . TABLE_PREFIX . "players_services` " .
				"WHERE `id` = '%d'",
				array($user_service['id'])
			));

			// Dodajemy expire
			if ($data['forever'])
				$set[] = "`expire` = '-1'";
			else
				$set[] = $db->prepare("`expire` = ( `expire`-UNIX_TIMESTAMP()+'%d' )", array(if_isset($data['expire'], $user_service['expire'])));

			$set_formatted = implode(", ", $set);

			// Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
			$db->query($db->prepare(
				"UPDATE `" . TABLE_PREFIX . "players_services` " .
				"SET {$set_formatted} " .
				"WHERE `id` = '%d'",
				array($user_service2['id'])
			));
		} else {
			// Dodajemy dane do zapytania
			$set[] = $db->prepare("`service` = '%s'", array($this->service['id']));
			if ($data['forever'])
				$set[] = "`expire` = '-1'";
			else if (isset($data['expire']))
				$set[] = $db->prepare("`expire` = '%d'", array($data['expire']));
			if (isset($data['server'])) $set[] = $db->prepare("`server` = '%d'", array($data['server']));
			if (isset($data['type'])) $set[] = $db->prepare("`type` = '%d'", array($data['type']));
			if (isset($data['auth_data'])) $set[] = $db->prepare("`auth_data` = '%s'", array($data['auth_data']));

			$set_formatted = implode(", ", $set);

			// Aktualizujemy usługę
			$db->query($db->prepare(
				"UPDATE `" . TABLE_PREFIX . "players_services` " .
				"SET {$set_formatted} " .
				"WHERE `id` = '%d'",
				array($user_service['id'])
			));
		}
		$affected = $db->affected_rows();

		// Ustaw jednakowe hasła
		// żeby potem nie było problemów z różnymi hasłami
		if (strlen($data['password']))
			$db->query($db->prepare(
				"UPDATE `" . TABLE_PREFIX . "players_services` " .
				"SET `password` = '%s' " .
				"WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
				array($data['password'], if_isset($data['server'], $user_service['server']), if_isset($data['type'], $user_service['type']),
					if_isset($data['auth_data'], $user_service['auth_data']))
			));

		// Przelicz flagi tylko wtedy, gdy coś się zmieniło
		if ($affected) {
			// Odśwież flagi gracza ( przed zmiana danych )
			$this->recalculate_player_flags($user_service['server'], $user_service['type'], $user_service['auth_data']);

			// Odśwież flagi gracza ( już po edycji )
			$this->recalculate_player_flags(if_isset($data['server'], $user_service['server']), if_isset($data['type'], $user_service['type']),
				if_isset($data['auth_data'], $user_service['auth_data']));

			return array(
				'status' => "edited",
				'text' => $lang['edited_user_service'],
				'positive' => true
			);
		} else {
			return array(
				'status' => "not_edited",
				'text' => $lang['not_edited_user_service'],
				'positive' => false
			);
		}
	}

	public function form_take_over_service($service_id)
	{
		global $heart, $lang;

		// Generujemy typy usługi
		$types = "";
		for ($i = 0; $i < 3; $i++) {
			$value = 1 << $i;
			if ($this->service['types'] & $value) {
				$types .= create_dom_element("option", get_type_name($value), array(
					'value' => $value
				));
			}
		}

		$servers = "";
		// Pobieranie listy serwerów
		foreach ($heart->get_servers() as $id => $row) {
			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		$module_id = $this::MODULE_ID;

		eval("\$output .= \"" . get_template("services/extra_flags/take_over_service") . "\";");

		return $output;
	}

	public function take_over_service($data)
	{
		global $db, $user, $settings, $lang;

		// Serwer
		if (!strlen($data['server']))
			$warnings['server'] = $lang['field_no_empty'];

		// Typ
		if (!strlen($data['type']))
			$warnings['type'] = $lang['field_no_empty'];

		switch ($data['type']) {
			case "1":
				// Nick
				if (!strlen($data['nick']))
					$warnings['nick'] = $lang['field_no_empty'];

				// Hasło
				if (!strlen($data['password']))
					$warnings['password'] = $lang['field_no_empty'];

				$auth_data = $data['nick'];

				break;

			case "2":
				// IP
				if (!strlen($data['ip']))
					$warnings['ip'] = $lang['field_no_empty'];

				// Hasło
				if (!strlen($data['password']))
					$warnings['password'] = $lang['field_no_empty'];

				$auth_data = $data['ip'];

				break;

			case "4":
				// SID
				if (!strlen($data['sid']))
					$warnings['sid'] = $lang['field_no_empty'];

				$auth_data = $data['sid'];

				break;
		}

		// Płatność
		if (!strlen($data['payment']))
			$warnings['payment'] = $lang['field_no_empty'];

		if (in_array($data['payment'], array("sms", "transfer")))
			if (!strlen($data['payment_id']))
				$warnings['payment_id'] = $lang['field_no_empty'];

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings)) {
			return array(
				'status' => "warnings",
				'text' => $lang['form_wrong_filled'],
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);
		}

		if ($data['payment'] == "transfer") {
			$result = $db->query($db->prepare(
				"SELECT * FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.payment = 'transfer' AND t.payment_id = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
				array($data['payment_id'], $this->service['id'], $data['server'], $auth_data)
			));

			if (!$db->num_rows($result))
				return array(
					'status' => "no_service",
					'text' => $lang['no_user_service'],
					'positive' => false
				);
		} else if ($data['payment'] == "sms") {
			$result = $db->query($db->prepare(
				"SELECT * FROM ({$settings['transactions_query']}) as t " .
				"WHERE t.payment = 'sms' AND t.sms_code = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
				array($data['payment_id'], $this->service['id'], $data['server'], $auth_data)
			));

			if (!$db->num_rows($result))
				return array(
					'status' => "no_service",
					'text' => $lang['no_user_service'],
					'positive' => false
				);
		}

		// TODO: Usunac md5
		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `password` = '%s' OR `password` = '%s' )",
			array($this->service['id'], $data['server'], $data['type'], $auth_data, $data['password'], md5($data['password']))
		));

		if (!$db->num_rows($result))
			return array(
				'status' => "no_service",
				'text' => $lang['no_user_service'],
				'positive' => false
			);

		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "players_services` " .
			"SET `uid` = '%d' " .
			"WHERE `service` = '%s' AND `type` = '%d' AND `auth_data` = '%s'",
			array($user['uid'], $data['service'], $data['type'], $auth_data)
		));

		if ($db->affected_rows())
			return array(
				'status' => "ok",
				'text' => $lang['service_taken_over'],
				'positive' => true
			);
		else
			return array(
				'status' => "service_not_taken_over",
				'text' => $lang['service_not_taken_over'],
				'positive' => false
			);
	}

	// ----------------------------------------------------------------------------------
	// ### Inne

	/**
	 * Metoda zwraca listę serwerów na których można zakupić daną usługę
	 *
	 * @param integer $server
	 * @return string            Lista serwerów w postaci <option value="id_serwera">Nazwa</option>
	 */
	private function servers_for_service($server)
	{
		global $lang;
		if (!get_privilages("manage_player_services")) {
			json_output("not_logged_in", $lang['no_access'], 0);
		}

		global $heart;

		$servers = "";
		// Pobieranie serwerów na których można zakupić daną usługę
		foreach ($heart->get_servers() as $id => $row) {
			// Usługi nie mozna kupic na tym serwerze
			if ($row[$this->service['id']] != "1")
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $server == $row['id'] ? "selected" : ""
			));
		}

		return $servers;
	}

	/**
	 * Funkcja zwraca listę dostępnych taryf danej usługi na danym lserwerze
	 *
	 * @param integer $server_id
	 * @return string
	 */
	private function tariffs_for_server($server_id)
	{
		global $heart, $db, $settings, $lang;

		$server = $heart->get_server($server_id);
		$sms_service = if_empty($server['sms_service'], $settings['sms_service']);

		// Pobieranie kwot za które można zakupić daną usługę na danym serwerze
		$result = $db->query($db->prepare(
			"SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.tariff AS `tariff`, p.amount AS `amount` " .
			"FROM `" . TABLE_PREFIX . "pricelist` AS p " .
			"JOIN `" . TABLE_PREFIX . "tariffs` AS t ON t.tariff = p.tariff " .
			"LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
			"WHERE p.service = '%s' AND ( p.server = '%d' OR p.server = '-1' ) " .
			"ORDER BY t.provision ASC",
			array($sms_service, $this->service['id'], $server_id)
		));

		while ($row = $db->fetch_array_assoc($result)) {
			$sms_cost = strlen($row['sms_number']) ? get_sms_cost($row['sms_number']) * $settings['vat'] : 0;
			$amount = $row['amount'] != -1 ? "{$row['amount']} {$this->service['tag']}" : $lang['forever'];
			eval("\$values .= \"" . get_template("services/extra_flags/purchase_value", false, true, false) . "\";");
		}

		eval("\$output = \"" . get_template("services/extra_flags/tariffs_for_server") . "\";");
		return $output;
	}

	// Zwraca wartość w zależności od typu
	private function get_auth_data($data)
	{
		if ($data['type'] == TYPE_NICK)
			return $data['nick'];
		else if ($data['type'] == TYPE_IP)
			return $data['ip'];
		else if ($data['type'] == TYPE_SID)
			return $data['sid'];
	}

	public function execute_action($action, $data)
	{
		switch ($action) {
			case "tariffs_for_server":
				return $this->tariffs_for_server(intval($data['server']));
			case "servers_for_service":
				return $this->servers_for_service(intval($data['server']));
		}
	}

	private function max_minus($a, $b)
	{
		if ($a == -1 || $b == -1)
			return -1;

		return max($a, $b);
	}

}