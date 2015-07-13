<?php

$heart->register_service_module("mybb_extra_groups", "Dodatkowe Grupy (MyBB)", "ServiceMybbExtraGroups", "ServiceMybbExtraGroupsSimple");

class ServiceMybbExtraGroupsSimple extends Service implements IService_AdminManage, IService_Create
{

	const MODULE_ID = "mybb_extra_groups";

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 */
	public function service_admin_extra_fields_get()
	{
		global $lang;

		// WEB
		if ($this->show_on_web()) $web_sel_yes = "selected";
		else $web_sel_no = "selected";

		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/extra_fields", 0, 1, 0) . "\";");
		return $output;
	}

	/**
	 * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
	 * jak coś się jej nie spodoba to zwraca o tym info w tablicy
	 *
	 * @param array $data Dane $_POST
	 * @return array        'key'    => DOM Element name
	 *                        'value'    => Error message
	 */
	public function service_admin_manage_pre($data)
	{
		global $lang;

		// Web
		if (!in_array($data['web'], array("1", "0")))
			$output['web'] = $lang->only_yes_no;

		// MyBB groups
		if (!strlen($_POST['groups_mybb']))
			$output['groups_mybb'] = $lang->field_no_empty;

		return $output;
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
		$extra_data['groups_mybb'] = trim($data['groups_mybb']);
		$extra_data['web'] = $data['web'];

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

		// Username
		if (!strlen($data['username']))
			$warnings['username'][] = $lang->field_no_empty;

		// Amount
		$amount = explode(';', $data['amount']); // Wyłuskujemy taryfę
		$tariff = $amount[2];

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
			'status' => "validated",
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
	 * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
	 *
	 * @param string $action Do czego zostaną te dane użyte ( email, web, payment_log )
	 *                            email - wiadomość wysłana na maila o zakupie usługi
	 *                            web - informacje wyświetlone na stronie WWW zaraz po zakupie
	 *                            payment_log - wpis w historii płatności
	 * @param array $data Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
	 * @return string        Informacje o zakupionej usłudze
	 */
	public function purchase_info($action, $data)
	{
		// TODO: Implement purchase_info() method.
	}

	/**
	 * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
	 *
	 * @param array $data user:
	 *                            uid - id uzytkownika wykonującego zakupy
	 *                            ip - ip użytkownika wykonującego zakupy
	 *                            email - email -||-
	 *                            name - nazwa -||-
	 *                        transaction:
	 *                            method - sposób płatności
	 *                            payment_id - id płatności
	 *                        order:
	 *                            server - serwer na który ma być wykupiona usługa
	 *                            auth_data - dane rozpoznawcze gracza
	 *                            type - TYPE_NICK / TYPE_IP / TYPE_SID
	 *                            password - hasło do usługi
	 *                            amount - ilość kupionej usługi
	 *
	 * @return integer        value returned by function add_bought_service_info
	 */
	public function purchase($data)
	{
		// TODO: Implement purchase() method.
	}
}