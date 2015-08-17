<?php

use Admin\Table;

$heart->register_service_module("mybb_extra_groups", "Dodatkowe Grupy (MyBB)", "ServiceMybbExtraGroups", "ServiceMybbExtraGroupsSimple");

class ServiceMybbExtraGroupsSimple extends Service implements IService_AdminManage, IService_Create, IService_UserServiceAdminDisplay
{

	const MODULE_ID = "mybb_extra_groups";
	const USER_SERVICE_TABLE = "user_service_mybb_extra_groups";

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 *
	 * @return string
	 */
	public function service_admin_extra_fields_get()
	{
		global $lang, $templates;

		// WEB
		if ($this->show_on_web()) $web_sel_yes = "selected";
		else $web_sel_no = "selected";

		// Jeżeli edytujemy
		if ($this->service !== NULL) {
			// DB
			$db_password = strlen($this->service['data']['db_password']) ? "********" : "";
			$db_host = htmlspecialchars($this->service['data']['db_host']);
			$db_user = htmlspecialchars($this->service['data']['db_user']);
			$db_name = htmlspecialchars($this->service['data']['db_name']);

			// MyBB groups
			$mybb_groups = htmlspecialchars($this->service['data']['mybb_groups']);
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/extra_fields", true, false));
		return $output;
	}

	/**
	 * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
	 * jak coś się jej nie spodoba to zwraca o tym info w tablicy
	 *
	 * @param array $data Dane $_POST
	 * @return array        'key' => DOM Element name
	 *                      'value' => Array of error messages
	 */
	public function service_admin_manage_pre($data)
	{
		global $lang;

		$warnings = array();

		// Web
		if (!in_array($data['web'], array("1", "0")))
			$warnings['web'][] = $lang->only_yes_no;

		// MyBB groups
		if (!strlen($data['mybb_groups']))
			$warnings['mybb_groups'][] = $lang->field_no_empty;
		else {
			$groups = explode(",", $data['mybb_groups']);
			foreach ($groups as $group) {
				if (!my_is_integer($group)) {
					$warnings['mybb_groups'][] = $lang->group_not_integer;
					break;
				}
			}
		}

		// Db host
		if (!strlen($data['db_host']))
			$warnings['db_host'][] = $lang->field_no_empty;

		// Db user
		if (!strlen($data['db_user']))
			$warnings['db_user'][] = $lang->field_no_empty;

		// Db password
		if ($this->service === NULL && !strlen($data['db_password']))
			$warnings['db_password'][] = $lang->field_no_empty;

		// Db name
		if (!strlen($data['db_name']))
			$warnings['db_name'][] = $lang->field_no_empty;

		return $warnings;
	}

	/**
	 * Metoda zostaje wywołana po tym, jak  weryfikacja danych
	 * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
	 *
	 * @param array $data Dane $_POST
	 * @return array (
	 *    'query_set' - array of query SET elements:
	 *        array(
	 *            'type'    => '%s'|'%d'|'%f'|'%c'|etc.
	 *            'column'    => kolumna
	 *            'value'    => wartość kolumny
	 *        )
	 */
	public function service_admin_manage_post($data)
	{
		$mybb_groups = explode(",", $data['mybb_groups']);
		foreach ($mybb_groups as $key => $group) {
			$mybb_groups[$key] = trim($group);
			if (!strlen($mybb_groups[$key]))
				unset($mybb_groups[$key]);
		}

		$extra_data = array(
			'mybb_groups' => implode(",", $mybb_groups),
			'web' => $data['web'],
			'db_host' => $data['db_host'],
			'db_user' => $data['db_user'],
			'db_password' => if_strlen($data['db_password'], $this->service['data']['db_password']),
			'db_name' => $data['db_name'],
		);

		// TODO: Zmiana grup i jej wpływ na obecne usługi

		return array(
			'query_set' => array(
				array(
					'type' => '%s',
					'column' => 'data',
					'value' => json_encode($extra_data)
				)
			)
		);
	}

	public function user_service_admin_display_title_get()
	{
		global $lang;
		return $lang->mybb_groups;
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
		$table->addHeadCell(new Table\Cell($lang->service));
		$table->addHeadCell(new Table\Cell($lang->mybb_user));
		$table->addHeadCell(new Table\Cell($lang->expires));

		// Wyszukujemy dane ktore spelniaja kryteria
		$where = '';
		if (isset($get['search']))
			searchWhere(array("us.id", "us.uid", "u.username", "s.name", "usmeg.mybb_uid"), urldecode($get['search']), $where);
		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . ' ';

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS us.id, us.uid, u.username, " .
			"s.id AS `service_id`, s.name AS `service`, us.expire, usmeg.mybb_uid " .
			"FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` AS usmeg ON usmeg.us_id = us.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON s.id = usmeg.service " .
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
			$body_row->addCell(new Table\Cell($row['service']));
			$body_row->addCell(new Table\Cell($row['mybb_uid']));
			$body_row->addCell(new Table\Cell($row['expire'] == '-1' ? $lang->never : date($settings['date_format'], $row['expire'])));
			if (get_privilages("manage_user_services")) {
				$body_row->setButtonDelete(false);
				$body_row->setButtonEdit(false);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->table = $table;

		return $wrapper;
	}
}

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements IService_Purchase, IService_PurchaseWeb, IService_UserOwnServices
{
	/**
	 * @var array
	 */
	private $groups;

	private $db_host;
	private $db_user;
	private $db_password;
	private $db_name;

	/**
	 * @var Database
	 */
	private $db_mybb = null;

	function __construct($service)
	{
		parent::__construct($service);

		$this->groups = explode(",", $this->service['data']['mybb_groups']);
		$this->db_host = if_isset($this->service['data']['db_host'], "");
		$this->db_user = if_isset($this->service['data']['db_user'], "");
		$this->db_password = if_isset($this->service['data']['db_password'], "");
		$this->db_name = if_isset($this->service['data']['db_name'], "");
	}

	/**
	 * Metoda powinna zwracać formularz zakupu w postaci stringa
	 *
	 * @return string   - Formularz zakupu
	 */
	public function purchase_form_get()
	{
		global $db, $user, $settings, $lang, $templates;

		// Pozyskujemy taryfy
		$result = $db->query($db->prepare(
			"SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.tariff AS `tariff`, p.amount AS `amount` " .
			"FROM `" . TABLE_PREFIX . "pricelist` AS p " .
			"INNER JOIN `" . TABLE_PREFIX . "tariffs` AS t ON t.tariff = p.tariff " .
			"LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
			"WHERE p.service = '%s' " .
			"ORDER BY t.provision ASC",
			array($settings['sms_service'], $this->service['id'])
		));

		$amounts = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$sms_cost = strlen($row['sms_number']) ? number_format(get_sms_cost($row['sms_number']) / 100 * $settings['vat'], 2) : 0;
			$amount = $row['amount'] != -1 ? $row['amount'] . " " . $this->service['tag'] : $lang->forever;
			$provision = number_format($row['provision'] / 100, 2);
			$amounts .= eval($templates->render("services/" . $this::MODULE_ID . "/purchase_value", true, false));
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_form"));

		return $output;
	}

	/**
	 * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
	 * i trzeba sprawdzić, czy są one prawidłowe
	 *
	 * @param array $data Dane $_POST
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się przeprowadzić zakup czy nie
	 */
	public function purchase_form_validate($data)
	{
		global $db, $lang;

		// Amount
		$amount = explode(';', $data['amount']); // Wyłuskujemy taryfę
		$tariff = $amount[2];

		// Tariff
		if (!$tariff)
			$warnings['amount'][] = $lang->must_choose_amount;
		else {
			// Wyszukiwanie usługi o konkretnej cenie
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `service` = '%s' AND `tariff` = '%d'",
				array($this->service['id'], $tariff)
			));

			if (!$db->num_rows($result)) // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
				return array(
					'status' => "no_option",
					'text' => $lang->service_not_affordable,
					'positive' => false
				);

			$price = $db->fetch_array_assoc($result);
		}

		// Username
		if (!strlen($data['username']))
			$warnings['username'][] = $lang->field_no_empty;
		else {
			$this->connectMybb();

			$result = $this->db_mybb->query($this->db_mybb->prepare(
				"SELECT 1 FROM `mybb_users` " .
				"WHERE `username` = '%s'",
				array($data['username'])
			));

			if (!$this->db_mybb->num_rows($result))
				$warnings['username'][] = $lang->no_user;
		}

		// E-mail
		if ($warning = check_for_warnings("email", $data['email']))
			$warnings['email'] = array_merge((array)$warnings['email'], $warning);

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);

		$purchase_data = new Entity_Purchase();
		$purchase_data->setService($this->service['id']);
		$purchase_data->setOrder(array(
			'username' => $data['username'],
			'amount' => $price['amount'],
			'forever' => $price['amount'] == -1 ? true : false
		));
		$purchase_data->setEmail($data['email']);
		$purchase_data->setTariff($tariff);

		return array(
			'status' => "ok",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase_data' => $purchase_data
		);
	}

	/**
	 * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
	 *
	 * @param Entity_Purchase $purchase_data
	 * @return string        Szczegóły zamówienia
	 */
	public function order_details($purchase_data)
	{
		global $lang, $templates;

		$email = $purchase_data->getEmail() ? htmlspecialchars($purchase_data->getEmail()) : $lang->none;
		$username = htmlspecialchars($purchase_data->getOrder('username'));
		$amount = $purchase_data->getOrder('amount') != -1 ? ($purchase_data->getOrder('amount') . " " . $this->service['tag']) : $lang->forever;

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/order_details", true, false));
		return $output;
	}

	/**
	 * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
	 *
	 * @param Entity_Purchase $purchase_data
	 * @return integer        value returned by function add_bought_service_info
	 */
	public function purchase($purchase_data)
	{
		// Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
		if (($mybb_user = $this->createMybbUserByUsername($purchase_data->getOrder('username'))) === NULL) {
			global $lang_shop;
			log_info($lang_shop->sprintf($lang_shop->mybb_purchase_no_user, json_encode($purchase_data->getPayment())));
			die("Critical error occured");
		}

		$this->add_user_service($purchase_data->user->getUid(), $mybb_user->getUid(), $purchase_data->getOrder('amount'), $purchase_data->getOrder('forever'));
		foreach ($this->groups as $group) {
			$mybb_user->prolongShopGroup($group, $purchase_data->getOrder('amount') * 24 * 60 * 60);
		}
		$this->saveMybbUser($mybb_user);

		return add_bought_service_info(
			$purchase_data->user->getUid(), $purchase_data->user->getUsername(), $purchase_data->user->getLastIp(), $purchase_data->getPayment('method'),
			$purchase_data->getPayment('payment_id'), $this->service['id'], 0, $purchase_data->getOrder('amount'), $purchase_data->getOrder('username'), $purchase_data->getEmail(),
			array(
				'uid' => $mybb_user->getUid(),
				'groups' => implode(',', $this->groups)
			)
		);
	}

	/**
	 * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
	 *
	 * @param string $action Do czego zostaną te dane użyte ( email, web, payment_log )
	 *                            email - wiadomość wysłana na maila o zakupie usługi
	 *                            web - informacje wyświetlone na stronie WWW zaraz po zakupie
	 *                            payment_log - wpis w historii płatności
	 * @param array $data Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
	 * @return string|array        Informacje o zakupionej usłudze
	 */
	public function purchase_info($action, $data)
	{
		global $settings, $lang, $templates;

		$username = htmlspecialchars($data['auth_data']);
		$amount = $data['amount'] != -1 ? ($data['amount'] . " " . $this->service['tag']) : $lang->forever;
		$email = htmlspecialchars($data['email']);
		$cost = $data['cost'] ? (number_format($data['cost'] / 100.0, 2) . " " . $settings['currency']) : $lang->none;

		if ($action == "email")
			$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_info_email", true, false));
		else if ($action == "web")
			$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_info_web", true, false));
		else if ($action == "payment_log")
			return array(
				'text' => $output = $lang->sprintf($lang->mybb_group_bought, $this->service['name'], $username),
				'class' => "outcome"
			);

		return $output;
	}

	/**
	 * Dodaje graczowi usłguę
	 *
	 * @param $uid
	 * @param $mybb_uid
	 * @param $days
	 * @param $forever
	 */
	private function add_user_service($uid, $mybb_uid, $days, $forever)
	{
		global $db;

		// Dodajemy usługę gracza do listy usług
		// Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
		$result = $db->query($db->prepare(
			"SELECT `us_id` FROM `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
			"WHERE `service` = '%s' AND `mybb_uid` = '%d'",
			array($this->service['id'], $mybb_uid)
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
					'column' => 'mybb_uid',
					'value' => "'%d'",
					'data' => array($mybb_uid)
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
				"INSERT INTO `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` (`us_id`, `service`, `mybb_uid`) " .
				"VALUES ('%d', '%s', '%d')",
				array($user_service_id, $this->service['id'], $mybb_uid)
			));
		}
	}

	public function user_own_service_info_get($user_service, $button_edit)
	{
		global $settings, $lang, $templates;

		$expire = $user_service['expire'] == -1 ? $lang->never : date($settings['date_format'], $user_service['expire']);
		$service = $this->service['name'];
		$mybb_user = htmlspecialchars($user_service['mybb_uid']);

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/user_own_service"));

		return $output;
	}

	/**
	 * @param $username
	 * @return null|Entity_MyBB_User
	 */
	private function createMybbUserByUsername($username)
	{
		global $db;
		$this->connectMybb();

		$result = $this->db_mybb->query($this->db_mybb->prepare(
			"SELECT `uid`, `additionalgroups` " .
			"FROM `mybb_users` " .
			"WHERE `username` = '%s'",
			array($username)
		));

		if (!$this->db_mybb->num_rows($result))
			return NULL;

		$row_mybb = $this->db_mybb->fetch_array_assoc($result);

		$mybb_user = new Entity_MyBB_User($row_mybb['uid']);
		$mybb_user->setMybbAddGroups(explode(",", $row_mybb['additionalgroups']));

		$result = $db->query($db->prepare(
			"SELECT `gid`, UNIX_TIMESTAMP(`expire`) - UNIX_TIMESTAMP() as `expire` FROM `" . TABLE_PREFIX . "mybb_user_group` " .
			"WHERE `uid` = '%d'",
			array($row_mybb['uid'])
		));

		while ($row = $db->fetch_array_assoc($result)) {
			$mybb_user->setShopGroups(array(
				$row['gid'] => $row['expire']
			));
		}

		return $mybb_user;
	}

	/**
	 * Zapisuje dane o użytkowniku
	 *
	 * @param Entity_MyBB_User $mybb_user
	 */
	private function saveMybbUser($mybb_user)
	{
		global $db;
		$this->connectMybb();

		$values = array();
		foreach ($mybb_user->getShopGroups() as $gid => $expire) {
			$values[] = $db->prepare(
				"('%d', '%d', FROM_UNIXTIME(UNIX_TIMESTAMP() + %d), '%d')",
				array($mybb_user->getUid(), $gid, $expire, in_array($gid, $mybb_user->getMybbAddGroups()))
			);
		}

		if (!empty($values)) {
			$db->query(
				"INSERT INTO `" . TABLE_PREFIX . "mybb_user_group` (`uid`, `gid`, `expire`, `was_before`) " .
				"VALUES " . implode(", ", $values) . " " .
				"ON DUPLICATE KEY UPDATE " .
				"`expire` = VALUES(`expire`)"
			);
		}

		$addgroups = array_unique(array_merge(array_keys($mybb_user->getShopGroups()), $mybb_user->getMybbAddGroups()));

		$this->db_mybb->query($this->db_mybb->prepare(
			"UPDATE `mybb_users` " .
			"SET `additionalgroups` = '%s' " .
			"WHERE `uid` = '%d'",
			array(implode(',', $addgroups), $mybb_user->getUid())
		));
	}

	public function user_service_delete($user_service, $who)
	{
		global $db;
		$this->connectMybb();

		// TODO Zmień display group na 0
	}

	private function connectMybb()
	{
		if ($this->db_mybb !== NULL)
			return;

		$this->db_mybb = new Database($this->db_host, $this->db_user, $this->db_password, $this->db_name);
	}
}