<?php

use Admin\Table;

$heart->register_service_module("extra_flags", "Dodatkowe Uprawnienia / Flagi", "ServiceExtraFlags", "ServiceExtraFlagsSimple");

class ServiceExtraFlagsSimple extends Service implements IService_AdminManage, IService_Create, IService_AvailableOnServers, IService_UserServiceAdminDisplay
{

	const MODULE_ID = "extra_flags";
	const USER_SERVICE_TABLE = "user_service_extra_flags";

	public function service_admin_extra_fields_get()
	{
		global $lang, $templates;

		// WEB
		if ($this->show_on_web()) $web_sel_yes = "selected";
		else $web_sel_no = "selected";

		// Nick, IP, SID
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			$types .= create_dom_element("option", $this->get_type_name($option_id), array(
				'value' => $option_id,
				'selected' => $this->service !== NULL && $this->service['types'] & $option_id ? "selected" : ""
			));

		// Pobieramy flagi, jeżeli service nie jest puste
		// czyli kiedy edytujemy, a nie dodajemy usługę
		if ($this->service !== NULL)
			$flags = $this->service['flags_hsafe'];

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/extra_fields", true, false));

		return $output;
	}

	public function service_admin_manage_pre($data)
	{
		global $lang;

		$warnings = array();

		// Web
		if (!in_array($data['web'], array("1", "0")))
			$warnings['web'][] = $lang->only_yes_no;

		// Flagi
		if (!strlen($data['flags']))
			$warnings['flags'][] = $lang->field_no_empty;
		else if (strlen($data['flags']) > 25)
			$warnings['flags'][] = $lang->too_many_flags;
		else if (implode('', array_unique(str_split($data['flags']))) != $data['flags'])
			$warnings['flags'][] = $lang->same_flags;

		// Typy
		if (empty($data['type']))
			$warnings['type[]'][] = $lang->no_type_chosen;

		// Sprawdzamy, czy typy są prawidłowe
		foreach ($data['type'] as $type)
			if (!($type & (TYPE_NICK | TYPE_IP | TYPE_SID))) {
				$warnings['type[]'][] = $lang->wrong_type_chosen;
				break;
			}

		return $warnings;
	}

	public function service_admin_manage_post($data)
	{
		global $settings, $lang;

		// Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
		$types = 0;
		foreach ($data['type'] as $type) {
			$types |= $type;
		}

		$extra_data = $this->service['data'];
		$extra_data['web'] = $data['web'];

		// Tworzymy plik z opisem usługi
		$file = SCRIPT_ROOT . "themes/{$settings['theme']}/services/" . escape_filename($data['id']) . "_desc.html";
		if (!file_exists($file)) {
			file_put_contents($file, "");

			// Dodajemy uprawnienia
			chmod($file, 0777);

			// Sprawdzamy czy uprawnienia się dodały
			if (substr(sprintf('%o', fileperms($file)), -4) != "0777")
				json_output("not_created", $lang->sprintf($lang->wrong_service_description_file, $settings['theme']), 0);
		}

		return array(
			'query_set' => array(
				array(
					'type' => '%d',
					'column' => 'types',
					'value' => $types
				),
				array(
					'type' => '%s',
					'column' => 'flags',
					'value' => $data['flags']
				),
				array(
					'type' => '%s',
					'column' => 'data',
					'value' => json_encode($extra_data)
				)
			)
		);
	}

	// Zwraca nazwę typu
	protected function get_type_name($value)
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

	protected function get_type_name2($value)
	{
		global $lang;

		if ($value == TYPE_NICK)
			return $lang->nick;
		else if ($value == TYPE_IP)
			return $lang->ip;
		else if ($value == TYPE_SID)
			return $lang->sid;

		return "";
	}

	// ----------------------------------------------------------------------------------
	// ### Wyświetlanie usług użytkowników w PA

	public function user_service_admin_display_title_get()
	{
		global $lang;
		return $lang->extra_flags;
	}

	public function user_service_admin_display_get($get, $post)
	{
		global $db, $settings, $lang, $G_PAGE;

		$wrapper = new Table\Wrapper();
		$wrapper->setSearch();

		$table = new Table\Structure();

		$cell = new Table\Cell($lang->id);
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Table\Cell($lang->user));
		$table->addHeadCell(new Table\Cell($lang->server));
		$table->addHeadCell(new Table\Cell($lang->service));
		$table->addHeadCell(new Table\Cell("{$lang->nick}/{$lang->ip}/{$lang->sid}"));
		$table->addHeadCell(new Table\Cell($lang->expires));

		// Wyszukujemy dane ktore spelniaja kryteria
		$where = '';
		if (isset($get['search']))
			searchWhere(array("us.id", "us.uid", "u.username", "srv.name", "s.name", "usef.auth_data"), urldecode($get['search']), $where);
		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . ' ';

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS us.id AS `id`, us.uid AS `uid`, u.username AS `username`, " .
			"srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
			"usef.type AS `type`, usef.auth_data AS `auth_data`, us.expire AS `expire` " .
			"FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` AS usef ON usef.us_id = us.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON s.id = usef.service " .
			"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON srv.id = usef.server " .
			"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = us.uid " .
			$where .
			"ORDER BY us.id DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new Table\BodyRow();

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Table\Cell($row['uid'] ? $row['username'] . " ({$row['uid']})" : $lang->none));
			$body_row->addCell(new Table\Cell($row['server']));
			$body_row->addCell(new Table\Cell($row['service']));
			$body_row->addCell(new Table\Cell($row['auth_data']));
			$body_row->addCell(new Table\Cell($row['expire'] == '-1' ? $lang->never : date($settings['date_format'], $row['expire'])));
			if (get_privilages("manage_user_services")) {
				$body_row->setButtonDelete();
				$body_row->setButtonEdit();
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->table = $table;

		return $wrapper;
	}

}

class ServiceExtraFlags extends ServiceExtraFlagsSimple implements IService_Purchase, IService_PurchaseWeb, IService_PurchaseOutside,
	IService_UserServiceAdminAdd, IService_UserServiceAdminEdit, IService_ActionExecute, IService_UserOwnServices,
	IService_UserOwnServicesEdit, IService_TakeOver, IService_ServiceCode, IService_ServiceCodeAdminManage
{

	function __construct($service)
	{
		// Wywolujemy konstruktor klasy ktora rozszerzamy
		parent::__construct($service);

		$this->service['flags_hsafe'] = htmlspecialchars($this->service['flags']);
	}

	public function purchase_form_get()
	{
		global $heart, $lang, $settings, $user, $templates;

		// Generujemy typy usługi
		$types = "";
		for ($i = 0, $value = 1; $i < 3; $value = 1 << ++$i)
			if ($this->service['types'] & $value) {
				$type = get_type_name($value);
				$types .= eval($templates->render("services/" . $this::MODULE_ID . "/service_type"));
			}

		// Pobieranie serwerów na których można zakupić daną usługę
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			// Usługi nie mozna kupic na tym serwerze
			if (!$heart->server_service_linked($id, $this->service['id']))
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_form"));

		return $output;
	}

	public function purchase_form_validate($data)
	{
		// Wyłuskujemy taryfę
		$value = explode(';', $data['value']);

		// Pobieramy auth_data
		$auth_data = $this->get_auth_data($data);

		$purchase_data = new Entity_Purchase();
		$purchase_data->setOrder(array(
			'server' => $data['server'],
			'type' => $data['type'],
			'auth_data' => trim($auth_data),
			'password' => $data['password'],
			'passwordr' => $data['password_repeat']
		));
		$purchase_data->setTariff($value[2]);
		$purchase_data->setEmail($data['email']);

		return $this->purchase_data_validate($purchase_data);
	}

	public function purchase_data_validate($purchase_data)
	{
		global $heart, $db, $lang;

		$warnings = array();

		// Serwer
		if (!strlen($purchase_data->getOrder('server')))
			$warnings['server'][] = $lang->must_choose_server;
		else {
			// Sprawdzanie czy serwer o danym id istnieje w bazie
			$server = $heart->get_server($purchase_data->getOrder('server'));
			if (!$heart->server_service_linked($server['id'], $this->service['id']))
				$warnings['server'][] = $lang->chosen_incorrect_server;
		}

		// Wartość usługi
		if (!$purchase_data->getTariff())
			$warnings['value'][] = $lang->must_choose_amount;
		else {
			// Wyszukiwanie usługi o konkretnej cenie
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
				array($this->service['id'], $purchase_data->getTariff(), $server['id'])
			));

			if (!$db->num_rows($result)) // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
				return array(
					'status' => "no_option",
					'text' => $lang->service_not_affordable,
					'positive' => false
				);

			$price = $db->fetch_array_assoc($result);
		}

		// Typ usługi
		// Mogą być tylko 3 rodzaje typu
		if ($purchase_data->getOrder('type') != TYPE_NICK && $purchase_data->getOrder('type') != TYPE_IP && $purchase_data->getOrder('type') != TYPE_SID)
			$warnings['type'][] = $lang->must_choose_type;
		else if (!($this->service['types'] & $purchase_data->getOrder('type')))
			$warnings['type'][] = $lang->chosen_incorrect_type;
		else if ($purchase_data->getOrder('type') & (TYPE_NICK | TYPE_IP)) {
			// Nick
			if ($purchase_data->getOrder('type') == TYPE_NICK) {
				if ($warning = check_for_warnings("nick", $purchase_data->getOrder('auth_data')))
					$warnings['nick'] = array_merge((array)$warnings['nick'], $warning);

				// Sprawdzanie czy istnieje już taka usługa
				$query = $db->prepare(
					"SELECT `password` FROM `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
					"WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
					array(TYPE_NICK, $purchase_data->getOrder('auth_data'), $server['id'])
				);
			} // IP
			else if ($purchase_data->getOrder('type') == TYPE_IP) {
				if ($warning = check_for_warnings("ip", $purchase_data->getOrder('auth_data')))
					$warnings['ip'] = array_merge((array)$warnings['ip'], $warning);

				// Sprawdzanie czy istnieje już taka usługa
				$query = $db->prepare(
					"SELECT `password` FROM `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
					"WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
					array(TYPE_IP, $purchase_data->getOrder('auth_data'), $server['id'])
				);
			}

			// Hasło
			if ($warning = check_for_warnings("password", $purchase_data->getOrder('password')))
				$warnings['password'] = array_merge((array)$warnings['password'], $warning);
			if ($purchase_data->getOrder('password') != $purchase_data->getOrder('passwordr'))
				$warnings['password_repeat'][] = $lang->passwords_not_match;

			// Sprawdzanie czy istnieje już taka usługa
			if ($temp_password = $db->get_column($query, 'password'))
				// TODO: Usunąć md5 w przyszłości
				if ($temp_password != $purchase_data->getOrder('password') && $temp_password != md5($purchase_data->getOrder('password')))
					$warnings['password'][] = $lang->existing_service_has_different_password;

			unset($temp_password);
		} // SteamID
		else
			if ($warning = check_for_warnings("sid", $purchase_data->getOrder('auth_data')))
				$warnings['sid'] = array_merge((array)$warnings['sid'], $warning);

		// E-mail
		if ((strpos($purchase_data->user->getPlatform(), "engine") !== 0 || strlen($purchase_data->getEmail())) && $warning = check_for_warnings("email", $purchase_data->getEmail()))
			$warnings['email'] = array_merge((array)$warnings['email'], $warning);

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);

		$purchase_data->setOrder(array(
			'amount' => $price['amount'],
			'forever' => $price['amount'] == -1 ? true : false
		));

		if (strlen($server['sms_service']))
			$purchase_data->setPayment(array(
				'sms_service' => $server['sms_service']
			));

		return array(
			'status' => "ok",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase_data' => $purchase_data
		);
	}

	public function order_details($purchase_data)
	{
		global $heart, $lang, $templates;

		$server = $heart->get_server($purchase_data->getOrder('server'));
		$type_name = $this->get_type_name2($purchase_data->getOrder('type'));
		if (strlen($purchase_data->getOrder('password')))
			$password = "<strong>{$lang->password}</strong>: " . htmlspecialchars($purchase_data->getOrder('password')) . "<br />";
		$email = strlen($purchase_data->getEmail()) ? htmlspecialchars($purchase_data->getEmail()) : $lang->none;
		$auth_data = htmlspecialchars($purchase_data->getOrder('auth_data'));
		$amount = !$purchase_data->getOrder('forever') ? ($purchase_data->getOrder('amount') . " " . $this->service['tag']) : $lang->forever;

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/order_details", true, false));
		return $output;
	}

	public function purchase($purchase_data)
	{
		$this->add_player_flags(
			$purchase_data->user->getUid(), $purchase_data->getOrder('type'), $purchase_data->getOrder('auth_data'), $purchase_data->getOrder('password'),
			$purchase_data->getOrder('amount'), $purchase_data->getOrder('server'), $purchase_data->getOrder('forever')
		);

		return add_bought_service_info(
			$purchase_data->user->getUid(), $purchase_data->user->getUsername(), $purchase_data->user->getLastip(), $purchase_data->getPayment('method'),
			$purchase_data->getPayment('payment_id'), $this->service['id'], $purchase_data->getOrder('server'), $purchase_data->getOrder('amount'),
			$purchase_data->getOrder('auth_data'), $purchase_data->getEmail(), array(
				'type' => $purchase_data->getOrder('type'),
				'password' => $purchase_data->getOrder('password')
			)
		);
	}

	private function add_player_flags($uid, $type, $auth_data, $password, $days, $server_id, $forever = false)
	{
		global $db;

		$auth_data = trim($auth_data);

		// Usunięcie przestarzałych usług gracza
		delete_users_old_services();

		// Usunięcie przestarzałych flag graczy
		// Tak jakby co
		$this->delete_old_flags();

		// Dodajemy usługę gracza do listy usług
		// Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
		$result = $db->query($db->prepare(
			"SELECT `us_id` FROM `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
			"WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
			array($this->service['id'], $server_id, $type, $auth_data)
		));

		if ($db->num_rows($result)) { // Aktualizujemy
			$row = $db->fetch_array_assoc($result);
			$user_service_id = $row['us_id'];

			$this->update_user_service(array(
				array(
					'column' => 'uid',
					'value' => "'%d'",
					'data' => array($uid)
				),
				array(
					'column' => 'password',
					'value' => "'%s'",
					'data' => array($password)
				),
				array(
					'column' => 'expire',
					'value' => "IF('%d' = '1', -1, `expire` + '%d')",
					'data' => array($forever, $days * 24 * 60 * 60)
				)
			), $user_service_id, $user_service_id);
		} else { // Wstawiamy
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "user_service` (`uid`, `service`, `expire`) " .
				"VALUES ('%d', '%s', IF('%d' = '1', '-1', UNIX_TIMESTAMP() + '%d')) ",
				array($uid, $this->service['id'], $forever, $days * 24 * 60 * 60)
			));
			$user_service_id = $db->last_id();

			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` (`us_id`, `server`, `service`, `type`, `auth_data`, `password`) " .
				"VALUES ('%d', '%d', '%s', '%d', '%s', '%s')",
				array($user_service_id, $server_id, $this->service['id'], $type, $auth_data, $password)
			));
		}

		// Ustawiamy jednakowe hasła dla wszystkich usług tego gracza na tym serwerze
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
			"SET `password` = '%s' " .
			"WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
			array($password, $server_id, $type, $auth_data)
		));

		// Przeliczamy flagi gracza, ponieważ dodaliśmy nową usługę
		$this->recalculate_player_flags($server_id, $type, $auth_data);
	}

	private function delete_old_flags()
	{
		global $db;

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
	}

	private function recalculate_player_flags($server_id, $type, $auth_data)
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
			"WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
			array($server_id, $type, $auth_data)
		));

		// Pobieranie wszystkich usług na konkretne dane
		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` AS usef ON us.id = usef.us_id " .
			"WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )",
			array($server_id, $type, $auth_data)
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
		$set = '';
		foreach ($flags as $flag => $amount)
			$set .= $db->prepare(", `%s` = '%d'", array($flag, $amount));

		// Dodanie flag
		if (strlen($set))
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "players_flags` " .
				"SET `server` = '%d', `type` = '%d', `auth_data` = '%s', `password` = '%s'{$set}",
				array($server_id, $type, $auth_data, $password)
			));
	}

	public function purchase_info($action, $data)
	{
		global $heart, $settings, $lang, $templates;

		$data['extra_data'] = json_decode($data['extra_data'], true);
		$data['extra_data']['type_name'] = $this->get_type_name2($data['extra_data']['type']);
		if (strlen($data['extra_data']['password']))
			$password = "<strong>{$lang->password}</strong>: " . htmlspecialchars($data['extra_data']['password']) . "<br />";
		$amount = $data['amount'] != -1 ? "{$data['amount']} {$this->service['tag']}" : $lang->forever;
		$data['auth_data'] = htmlspecialchars($data['auth_data']);
		$data['extra_data']['password'] = htmlspecialchars($data['extra_data']['password']);
		$data['email'] = htmlspecialchars($data['email']);
		$cost = $data['cost'] ? number_format($data['cost'] / 100.0, 2) . " " . $settings['currency'] : $lang->none;
		$data['income'] = number_format($data['income'] / 100.0, 2);

		if ($data['payment'] == "sms") {
			$data['sms_code'] = htmlspecialchars($data['sms_code']);
			$data['sms_text'] = htmlspecialchars($data['sms_text']);
			$data['sms_number'] = htmlspecialchars($data['sms_number']);
		}

		$server = $heart->get_server($data['server']);

		if ($data['extra_data']['type'] & (TYPE_NICK | TYPE_IP))
			$setinfo = $lang->sprintf($lang->type_setinfo, htmlspecialchars($data['extra_data']['password']));

		if ($action == "email")
			$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_info_email", true, false));
		else if ($action == "web")
			$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_info_web", true, false));
		else if ($action == "payment_log")
			return array(
				'text' => $output = $lang->sprintf($lang->service_was_bought, $this->service['name'], $server['name']),
				'class' => "outcome"
			);

		return $output;
	}

	// ----------------------------------------------------------------------------------
	// ### Zarządzanie usługami użytkowników przez admina

	public function user_service_admin_add_form_get()
	{
		global $heart, $settings, $lang, $templates;

		// Pobieramy listę typów usługi, (1<<2) ostatni typ
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			if ($this->service['types'] & $option_id)
				$types .= create_dom_element("option", $this->get_type_name($option_id), array(
					'value' => $option_id
				));

		// Pobieramy listę serwerów
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			if (!$heart->server_service_linked($id, $this->service['id']))
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		return eval($templates->render("services/" . $this::MODULE_ID . "/user_service_admin_add", true, false));
	}

	//
	// Funkcja dodawania usługi przez PA
	//
	public function user_service_admin_add($data)
	{
		global $heart, $lang, $lang_shop, $user;

		$warnings = array();

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Sprawdzamy hasło, jeżeli podano nick albo ip
		if ($data['type'] & (TYPE_NICK | TYPE_IP) && $warning = check_for_warnings("password", $data['password']))
			$warnings['password'] = array_merge((array)$warnings['password'], $warning);

		// Amount
		if (!$data['forever']) {
			if ($warning = check_for_warnings("number", $data['amount']))
				$warnings['amount'] = array_merge((array)$warnings['amount'], $warning);
			else if ($data['amount'] < 0)
				$warnings['amount'][] = $lang->days_quantity_positive;
		}

		// E-mail
		if (strlen($data['email']) && $warning = check_for_warnings("email", $data['email']))
			$warnings['email'] = array_merge((array)$warnings['email'], $warning);

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		//
		// Dodajemy usługę

		// Dodawanie informacji o płatności
		$payment_id = pay_by_admin($user);

		$purchase_data = new Entity_Purchase();
		$purchase_data->setService($this->service['id']);
		$purchase_data->user = $heart->get_user($data['uid']); // Pobieramy dane o użytkowniku na które jego wykupiona usługa
		$purchase_data->setPayment(array(
			'method' => "admin",
			'payment_id' => $payment_id
		));
		$purchase_data->setOrder(array(
			'server' => $data['server'],
			'type' => $data['type'],
			'auth_data' => trim($data['auth_data']),
			'password' => $data['password'],
			'amount' => $data['amount'],
			'forever' => (boolean)$data['forever']
		));
		$purchase_data->setEmail($data['email']);
		$bought_service_id = $this->purchase($purchase_data);

		log_info($lang_shop->sprintf($lang_shop->admin_added_service, $user->getUsername(), $user->getUid(), $bought_service_id));

		return array(
			'status' => "added",
			'text' => $lang->service_added_correctly,
			'positive' => true
		);
	}

	public function user_service_admin_edit_form_get($user_service)
	{
		global $heart, $settings, $lang, $templates;

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
				'selected' => $user_service['service'] == $row['id'] ? "selected" : ""
			));
		}

		// Dodajemy typ uslugi, (1<<2) ostatni typ
		$types = "";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i)
			if ($this->service['types'] & $option_id)
				$types .= create_dom_element("option", $this->get_type_name($option_id), array(
					'value' => $option_id,
					'selected' => $option_id == $user_service['type'] ? "selected" : ""
				));

		if ($user_service['type'] == TYPE_NICK) {
			$nick = htmlspecialchars($user_service['auth_data']);
			$styles['nick'] = $styles['password'] = "display: table-row-group";
		} else if ($user_service['type'] == TYPE_IP) {
			$ip = htmlspecialchars($user_service['auth_data']);
			$styles['ip'] = $styles['password'] = "display: table-row-group";
		} else if ($user_service['type'] == TYPE_SID) {
			$sid = htmlspecialchars($user_service['auth_data']);
			$styles['sid'] = "display: table-row-group";
		}

		// Pobranie serwerów
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			if (!$heart->server_service_linked($id, $this->service['id']))
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $user_service['server'] == $row['id'] ? "selected" : ""
			));
		}

		// Pobranie hasła
		if (strlen($user_service['password']))
			$password = "********";

		// Zamiana daty
		if ($user_service['expire'] == -1) {
			$checked = "checked";
			$disabled = "disabled";
			$user_service['expire'] = "";
		} else
			$user_service['expire'] = date($settings['date_format'], $user_service['expire']);

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/user_service_admin_edit", true, false));

		return $output;
	}

	//
	// Funkcja edytowania usługi przez admina z PA
	//
	public function user_service_admin_edit($data, $user_service)
	{
		global $lang, $lang_shop, $user;

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Expire
		if (!$data['forever'] && ($data['expire'] = strtotime($data['expire'])) === FALSE)
			$warnings['expire'][] = $lang->wrong_date_format;
		// Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
		if (!strlen($data['password']) && $data['type'] & (TYPE_NICK | TYPE_IP) && !strlen($user_service['password']))
			$warnings['password'][] = $lang->field_no_empty;

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		//
		// Aktualizujemy usługę
		$edit_return = $this->user_service_edit($user_service, $data);

		if ($edit_return['status'] == "edited")
			log_info($lang_shop->sprintf($lang_shop->admin_edited_user_service, $user->getUsername(), $user->getUid(), $user_service['id']));

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
				$warnings['uid'] = array_merge((array)$warnings['uid'], $warning);
			else {
				$user2 = $heart->get_user($data['uid']);
				if (!$user2->isLogged())
					$warnings['uid'][] = $lang->no_account_id;
			}
		}

		// Typ usługi
		// Mogą być tylko 3 rodzaje typu
		if ($data['type'] != TYPE_NICK && $data['type'] != TYPE_IP && $data['type'] != TYPE_SID)
			$warnings['type'][] = $lang->must_choose_service_type;
		else if (!($this->service['types'] & $data['type']))
			$warnings['type'][] = $lang->forbidden_purchase_type;
		else if ($data['type'] & (TYPE_NICK | TYPE_IP)) {
			// Nick
			if ($data['type'] == TYPE_NICK && $warning = check_for_warnings("nick", $data['auth_data']))
				$warnings['nick'] = array_merge((array)$warnings['nick'], $warning);
			// IP
			else if ($data['type'] == TYPE_IP && $warning = check_for_warnings("ip", $data['auth_data']))
				$warnings['ip'] = array_merge((array)$warnings['ip'], $warning);

			// Hasło
			if (strlen($data['password']) && $warning = check_for_warnings("password", $data['password']))
				$warnings['password'] = array_merge((array)$warnings['password'], $warning);
		} // SteamID
		else if ($warning = check_for_warnings("sid", $data['auth_data']))
			$warnings['sid'] = array_merge((array)$warnings['sid'], $warning);

		// Server
		if ($server) {
			if (!strlen($data['server']))
				$warnings['server'][] = $lang->choose_server_for_service;
			// Wyszukiwanie serwera o danym id
			else if (($server = $heart->get_server($data['server'])) === NULL)
				$warnings['server'][] = $lang->no_server_id;
		}

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);
	}

	public function user_service_delete_post($user_service)
	{
		// Odśwież flagi gracza
		$this->recalculate_player_flags($user_service['server'], $user_service['type'], $user_service['auth_data']);
	}

	// ----------------------------------------------------------------------------------
	// ### Edytowanie usług przez użytkownika

	public function user_own_service_edit_form_get($user_service)
	{
		global $heart, $settings, $lang, $templates;

		// Dodajemy typ uslugi, (1<<2) ostatni typ
		$service_info = array();
		$styles['nick'] = $styles['ip'] = $styles['sid'] = $styles['password'] = "display: none";
		for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i) {
			// Kiedy dana usługa nie wspiera danego typu i wykupiona usługa nie ma tego typu
			if (!($this->service['types'] & $option_id) && $option_id != $user_service['type'])
				continue;

			$service_info['types'] .= create_dom_element("option", $this->get_type_name($option_id), array(
				'value' => $option_id,
				'selected' => $option_id == $user_service['type'] ? "selected" : ""
			));

			if ($option_id == $user_service['type']) {
				switch ($option_id) {
					case TYPE_NICK:
						$service_info['player_nick'] = htmlspecialchars($user_service['auth_data']);
						$styles['nick'] = $styles['password'] = "display: table-row";
						break;

					case TYPE_IP:
						$service_info['player_ip'] = htmlspecialchars($user_service['auth_data']);
						$styles['ip'] = $styles['password'] = "display: table-row";
						break;

					case TYPE_SID:
						$service_info['player_sid'] = htmlspecialchars($user_service['auth_data']);
						$styles['sid'] = "display: table-row";
						break;
				}
			}
		}

		// Hasło
		if (strlen($user_service['password']) && $user_service['password'] != md5(""))
			$service_info['password'] = "********";

		// Serwer
		$temp_server = $heart->get_server($user_service['server']);
		$service_info['server'] = $temp_server['name'];
		unset($temp_server);

		// Wygasa
		$service_info['expire'] = $user_service['expire'] == -1 ? $lang->never : date($settings['date_format'], $user_service['expire']);

		// Usługa
		$service_info['service'] = $this->service['name'];

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/user_own_service_edit"));

		return $output;
	}

	public function user_own_service_info_get($user_service, $button_edit)
	{
		global $heart, $settings, $lang, $templates;

		$service_info['expire'] = $user_service['expire'] == -1 ? $lang->never : date($settings['date_format'], $user_service['expire']);
		$temp_server = $heart->get_server($user_service['server']);
		$service_info['server'] = $temp_server['name'];
		$service_info['service'] = $this->service['name'];
		$service_info['type'] = $this->get_type_name2($user_service['type']);
		$service_info['auth_data'] = htmlspecialchars($user_service['auth_data']);
		unset($temp_server);

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/user_own_service"));

		return $output;
	}

	public function user_own_service_edit($data, $user_service)
	{
		global $lang, $lang_shop, $user;

		// Pobieramy auth_data
		$data['auth_data'] = $this->get_auth_data($data);

		// Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
		if (!strlen($data['password']) && $data['type'] & (TYPE_NICK | TYPE_IP) && !strlen($user_service['password']))
			$warnings['password'][] = $lang->field_no_empty;

		// Sprawdzamy poprawność wprowadzonych danych
		$verify_data = $this->verify_user_service_data($data, $warnings, false);

		// Jeżeli są jakieś błędy, to je zwracamy
		if (!empty($verify_data))
			return $verify_data;

		//
		// Aktualizujemy usługę

		$edit_return = $this->user_service_edit($user_service, array(
			'type' => $data['type'],
			'auth_data' => $data['auth_data'],
			'password' => $data['password']
		));

		if ($edit_return['status'] == "edited")
			log_info($lang_shop->sprintf($lang_shop->user_edited_service, $user->getUsername(), $user->getUid(), $user_service['id']));

		return $edit_return;
	}

	// ----------------------------------------------------------------------------------
	// ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

	private function user_service_edit($user_service, $data)
	{
		global $db, $lang;

		$set = array();
		// Dodanie hasła do zapytania
		if (strlen($data['password']))
			$set[] = array(
				'column' => 'password',
				'value' => "'%s'",
				'data' => array($data['password'])
			);

		// Dodajemy uid do zapytania
		if (isset($data['uid']))
			$set[] = array(
				'column' => 'uid',
				'value' => "'%d'",
				'data' => array($data['uid'])
			);

		// Dodajemy expire na zawsze
		if ($data['forever'])
			$set[] = array(
				'column' => 'expire',
				'value' => "-1",
			);

		// Sprawdzenie czy nie ma już takiej usługi
		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` AS usef ON us.id = usef.us_id " .
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
					'text' => $lang->service_isnt_yours,
					'positive' => false
				);

			// Usuwamy opcję którą aktualizujemy
			$db->query($db->prepare(
				"DELETE FROM `" . TABLE_PREFIX . "user_service` " .
				"WHERE `id` = '%d'",
				array($user_service['id'])
			));

			// Dodajemy expire
			if (!$data['forever'] && isset($data['expire']))
				$set[] = array(
					'column' => 'expire',
					'value' => "( `expire` - UNIX_TIMESTAMP() + '%d' )",
					'data' => array(if_isset($data['expire'], $user_service['expire']))
				);

			// Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
			$affected = $this->update_user_service($set, $user_service2['id'], $user_service2['id']);
		} else {
			$set[] = array(
				'column' => 'service',
				'value' => "'%s'",
				'data' => array($this->service['id'])
			);

			if (!$data['forever'] && isset($data['expire']))
				$set[] = array(
					'column' => 'expire',
					'value' => "'%d'",
					'data' => array($data['expire'])
				);

			if (isset($data['server']))
				$set[] = array(
					'column' => 'server',
					'value' => "'%d'",
					'data' => array($data['server'])
				);

			if (isset($data['type']))
				$set[] = array(
					'column' => 'type',
					'value' => "'%d'",
					'data' => array($data['type'])
				);

			if (isset($data['auth_data']))
				$set[] = array(
					'column' => 'auth_data',
					'value' => "'%s'",
					'data' => array($data['auth_data']),
				);

			$affected = $this->update_user_service($set, $user_service['id'], $user_service['id']);
		}

		// Ustaw jednakowe hasła
		// żeby potem nie było problemów z różnymi hasłami
		if (strlen($data['password']))
			$db->query($db->prepare(
				"UPDATE `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
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
				'text' => $lang->edited_user_service,
				'positive' => true
			);
		} else {
			return array(
				'status' => "not_edited",
				'text' => $lang->not_edited_user_service,
				'positive' => false
			);
		}
	}

	public function service_take_over_form_get()
	{
		global $heart, $lang, $templates;

		// Generujemy typy usługi
		$types = "";
		for ($i = 0; $i < 3; $i++) {
			$value = 1 << $i;
			if ($this->service['types'] & $value) {
				$types .= create_dom_element("option", $this->get_type_name($value), array(
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

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/service_take_over"));

		return $output;
	}

	public function service_take_over($data)
	{
		global $db, $user, $settings, $lang;

		// Serwer
		if (!strlen($data['server']))
			$warnings['server'][] = $lang->field_no_empty;

		// Typ
		if (!strlen($data['type']))
			$warnings['type'][] = $lang->field_no_empty;

		switch ($data['type']) {
			case "1":
				// Nick
				if (!strlen($data['nick']))
					$warnings['nick'][] = $lang->field_no_empty;

				// Hasło
				if (!strlen($data['password']))
					$warnings['password'][] = $lang->field_no_empty;

				$auth_data = $data['nick'];
				break;

			case "2":
				// IP
				if (!strlen($data['ip']))
					$warnings['ip'][] = $lang->field_no_empty;

				// Hasło
				if (!strlen($data['password']))
					$warnings['password'][] = $lang->field_no_empty;

				$auth_data = $data['ip'];
				break;

			case "4":
				// SID
				if (!strlen($data['sid']))
					$warnings['sid'][] = $lang->field_no_empty;

				$auth_data = $data['sid'];
				break;
		}

		// Płatność
		if (!strlen($data['payment']))
			$warnings['payment'][] = $lang->field_no_empty;

		if (in_array($data['payment'], array("sms", "transfer")))
			if (!strlen($data['payment_id']))
				$warnings['payment_id'][] = $lang->field_no_empty;

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings)) {
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
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
					'text' => $lang->no_user_service,
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
					'text' => $lang->no_user_service,
					'positive' => false
				);
		}

		// TODO: Usunac md5
		$result = $db->query($db->prepare(
			"SELECT `id` FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` AS usef ON us.id = usef.us_id " .
			"WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `password` = '%s' OR `password` = '%s' )",
			array($this->service['id'], $data['server'], $data['type'], $auth_data, $data['password'], md5($data['password']))
		));

		if (!$db->num_rows($result))
			return array(
				'status' => "no_service",
				'text' => $lang->no_user_service,
				'positive' => false
			);

		$row = $db->fetch_array_assoc($result);

		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
			"SET `uid` = '%d' " .
			"WHERE `id` = '%d'",
			array($user->getUid(), $row['id'])
		));

		if ($db->affected_rows())
			return array(
				'status' => "ok",
				'text' => $lang->service_taken_over,
				'positive' => true
			);
		else
			return array(
				'status' => "service_not_taken_over",
				'text' => $lang->service_not_taken_over,
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

		if (!get_privilages("manage_user_services")) {
			json_output("not_logged_in", $lang->no_access, 0);
		}

		global $heart;

		$servers = "";
		// Pobieranie serwerów na których można zakupić daną usługę
		foreach ($heart->get_servers() as $id => $row) {
			if (!$heart->server_service_linked($id, $this->service['id']))
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $server == $row['id'] ? "selected" : ""
			));
		}

		return $servers;
	}

	/**
	 * Funkcja zwraca listę dostępnych taryf danej usługi na danym serwerze
	 *
	 * @param integer $server_id
	 * @return string
	 */
	private function tariffs_for_server($server_id)
	{
		global $heart, $db, $settings, $lang, $templates;

		$server = $heart->get_server($server_id);
		$sms_service = if_strlen($server['sms_service'], $settings['sms_service']);

		// Pobieranie kwot za które można zakupić daną usługę na danym serwerze
		$result = $db->query($db->prepare(
			"SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.tariff AS `tariff`, p.amount AS `amount` " .
			"FROM `" . TABLE_PREFIX . "pricelist` AS p " .
			"INNER JOIN `" . TABLE_PREFIX . "tariffs` AS t ON t.tariff = p.tariff " .
			"LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
			"WHERE p.service = '%s' AND ( p.server = '%d' OR p.server = '-1' ) " .
			"ORDER BY t.provision ASC",
			array($sms_service, $this->service['id'], $server_id)
		));

		$values = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$provision = number_format($row['provision'] / 100, 2);
			$sms_cost = strlen($row['sms_number']) ? number_format(get_sms_cost($row['sms_number']) / 100 * $settings['vat'], 2) : 0;
			$amount = $row['amount'] != -1 ? "{$row['amount']} {$this->service['tag']}" : $lang->forever;
			$values .= eval($templates->render("services/" . $this::MODULE_ID . "/purchase_value", true, false));
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/tariffs_for_server"));
		return $output;
	}

	public function action_execute($action, $data)
	{
		switch ($action) {
			case "tariffs_for_server":
				return $this->tariffs_for_server(intval($data['server']));
			case "servers_for_service":
				return $this->servers_for_service(intval($data['server']));
		}
	}

	public function service_code_validate($purchase_data, $code)
	{
		return true;
	}

	public function service_code_admin_add_form_get()
	{
		global $heart, $lang, $templates;

		// Pobieramy listę serwerów
		$servers = "";
		foreach ($heart->get_servers() as $id => $row) {
			if (!$heart->server_service_linked($id, $this->service['id']))
				continue;

			$servers .= create_dom_element("option", $row['name'], array(
				'value' => $row['id']
			));
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/service_code_admin_add", true, false));
		return $output;
	}

	public function service_code_admin_add_validate($data)
	{
		global $heart, $lang;

		$warnings = array();

		// Serwer
		if (!strlen($data['server']))
			$warnings['server'][] = $lang->have_to_choose_server;
		// Wyszukiwanie serwera o danym id
		else if (($server = $heart->get_server($data['server'])) === NULL)
			$warnings['server'][] = $lang->no_server_id;

		// Taryfa
		$tariff = explode(';', $data['amount']);
		$tariff = $tariff[2];
		if (!strlen($data['amount']))
			$warnings['amount'][] = $lang->must_choose_quantity;
		else if (($heart->get_tariff($tariff)) === NULL)
			$warnings['amount'][] = $lang->no_such_tariff;

		return $warnings;
	}

	public function service_code_admin_add_insert($data)
	{
		$tariff = explode(';', $data['amount']);
		$tariff = $tariff[2];
		return array(
			'tariff' => $tariff,
			'server' => $data['server']
		);
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

	private function max_minus($a, $b)
	{
		if ($a == -1 || $b == -1)
			return -1;

		return max($a, $b);
	}
}