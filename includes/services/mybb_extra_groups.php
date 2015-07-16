<?php

$heart->register_service_module("mybb_extra_groups", "Dodatkowe Grupy (MyBB)", "ServiceMybbExtraGroups", "ServiceMybbExtraGroupsSimple");

class ServiceMybbExtraGroupsSimple extends Service implements IService_AdminManage, IService_Create
{

	const MODULE_ID = "mybb_extra_groups";

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 *
	 * @return string
	 */
	public function service_admin_extra_fields_get()
	{
		global $lang;

		// WEB
		if ($this->show_on_web()) $web_sel_yes = "selected";
		else $web_sel_no = "selected";

		if ($this->service !== NULL) {
			// DB
			$db_password = strlen($this->service['data']['db_password']) ? "********" : "";
			$db_host = htmlspecialchars($this->service['data']['db_host']);
			$db_user = htmlspecialchars($this->service['data']['db_user']);
			$db_name = htmlspecialchars($this->service['data']['db_name']);

			// MyBB groups
			$mybb_groups = htmlspecialchars($this->service['data']['mybb_groups']);
		}

		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/extra_fields", 0, 1, 0) . "\";");
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
			foreach($groups as $group) {
				$group = trim($group);
				if(strlen($group) && $group !== strval(intval($group))) {
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
	 *            'column'	=> kolumna
	 *            'value'	=> wartość kolumny
	 *        )
	 */
	public function service_admin_manage_post($data)
	{
		$mybb_groups = explode(",", $data['mybb_groups']);
		foreach($mybb_groups as $key => $group) {
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
			'query_set'	=> array(
				array(
					'type'	=> '%s',
					'column'=> 'data',
					'value'	=> json_encode($extra_data)
				)
			)
		);
	}
}

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements IService_Purchase, IService_PurchaseWeb
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

	function __construct($service) {
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
		global $db, $user, $settings, $lang;

		// Pozyskujemy taryfy
		$result = $db->query($db->prepare(
			"SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.tariff AS `tariff`, p.amount AS `amount` " .
			"FROM `" . TABLE_PREFIX . "pricelist` AS p " .
			"JOIN `" . TABLE_PREFIX . "tariffs` AS t ON t.tariff = p.tariff " .
			"LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
			"WHERE p.service = '%s' " .
			"ORDER BY t.provision ASC",
			array($settings['sms_service'], $this->service['id'])
		));

		$amount = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$sms_cost = strlen($row['sms_number']) ? get_sms_cost($row['sms_number']) * $settings['vat'] : 0;
			$amount = $row['amount'] != -1 ? $row['amount'] . " " . $this->service['tag'] : $lang->forever;
			eval("\$amounts .= \"" . get_template("services/" . $this::MODULE_ID . "/purchase_value", false, true, false) . "\";");
		}

		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/purchase_form") . "\";");

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

		$purchase = new Entity_Purchase(array(
			'service' => $this->service['id'],
			'order' => array(
				'auth_data' => $data['username'],
				'amount' => $price['amount'],
				'forever' => $price['amount'] == -1 ? true : false
			),
			'email' => $data['email'],
			'tariff' => $tariff,
		));

		return array(
			'status' => "ok",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase' => $purchase
		);
	}

	/**
	 * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
	 *
	 * @param Entity_Purchase $purchase
	 * @return string        Szczegóły zamówienia
	 */
	public function order_details($purchase)
	{
		global $lang;

		$email = $purchase->getEmail() ? htmlspecialchars($purchase->getEmail()) : $lang->none;
		$username = htmlspecialchars($purchase->getOrder('auth_data'));
		$amount = $purchase->getOrder('amount') != -1 ? ($purchase->getOrder('amount') . " " . $this->service['tag']) : $lang->forever;

		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/order_details", 0, 1, 0) . "\";");
		return $output;
	}

	/**
	 * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
	 *
	 * @param Entity_Purchase $purchase
	 * @return integer        value returned by function add_bought_service_info
	 */
	public function purchase($purchase)
	{
		// Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
		if (($mybb_user = $this->createMybbUserByUsername($purchase->getOrder('auth_data'))) === NULL) {
			// TODO: Dodać co ma się dziać
			return;
		}
		foreach ($this->groups as $group) {
			$mybb_user->prolongGroup($group, $purchase->getOrder('amount'));
		}
		$this->saveMybbUser($mybb_user);

		return add_bought_service_info(
			$purchase->getUser('uid'), $purchase->getUser('username'), $purchase->getUser('ip'), $purchase->getPayment('method'),
			$purchase->getPayment('payment_id'), $this->service['id'], 0, $purchase->getOrder('amount'), $purchase->getOrder('auth_data'), $purchase->getEmail()
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
		global $settings, $lang;

		$username = htmlspecialchars($data['auth_data']);
		$amount = $data['amount'] != -1 ? ($data['amount'] . " " . $this->service['tag']) : $lang->forever;
		$email = htmlspecialchars($data['email']);
		$cost = $data['cost'] ? (number_format($data['cost'], 2) . " " . $settings['currency']) : $lang->none;

		if ($action == "email")
			eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/purchase_info_email", false, true, false) . "\";");
		else if ($action == "web")
			eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/purchase_info_web", false, true, false) . "\";");
		else if ($action == "payment_log")
			return array(
				'text' => $output = $lang->sprintf($lang->mybb_group_bought, $this->service['name'], $username),
				'class' => "outcome"
			);

		return $output;
	}

	/**
	 * @param $username
	 * @return null|Entity_MyBB_User
	 */
	private function createMybbUserByUsername($username) {
		$this->connectMybb();

		$result = $this->db_mybb->query($this->db_mybb->prepare(
			"SELECT `uid`, `usergroup`, `additionalgroups` " .
			"FROM `mybb_users` " .
			"WHERE `username` = '%s'",
			array($username)
		));

		if (!$this->db_mybb->num_rows($result))
			return NULL;

		$row_mybb = $this->db_mybb->fetch_array_assoc($result);

		// TODO: Pozyskanie danych o kupionych grupach

		$mybb_user = new Entity_MyBB_User(array(
			'uid' => $row_mybb['uid']
		));

		return $mybb_user;
	}

	/**
	 * Zapisuje dane o użytkowniku
	 *
	 * @param Entity_MyBB_User $mybb_user
	 */
	private function saveMybbUser($mybb_user) {
		$this->connectMybb();

		// TODO: Zapisanie użytkownika do db oraz db_mybb
	}

	private function connectMybb() {
		if ($this->db_mybb !== NULL)
			return;

		$this->db_mybb = new Database($this->db_host, $this->db_user, $this->db_password, $this->db_name);
	}
}