<?php

$heart->register_service_module("other", "Inne", "ServiceOther", "ServiceOtherSimple");

class ServiceOtherSimple extends Service implements IService_Create
{

	const MODULE_ID = "other";

	public function service_admin_manage_post($data)
	{
		global $db;

		if ($data['action'] == "service_add")
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
	}

}

class ServiceOther extends ServiceOtherSimple implements IService_Purchase, IService_PurchaseOutside
{

	//
	// Funkcja przygotowania zakupu
	//
	public function purchase_validate_data($data)
	{
		global $heart, $db, $lang;

		$warnings = array();

		// Serwer
		$server = array();
		if (!strlen($data['order']['server']))
			$warnings['server'] .= $lang->must_choose_server . "<br />";
		else {
			// Sprawdzanie czy serwer o danym id istnieje w bazie
			$server = $heart->get_server($data['order']['server']);
			if (!$server[$this->service['id']])
				$warnings['server'] .= $lang->chosen_incorrect_server . "<br />";
		}

		// Wartość usługi
		$price = array();
		if (!strlen($data['tariff']))
			$warnings['value'] .= $lang->must_choose_amount . "<br />";
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
					'text' => $lang->service_not_affordable,
					'positive' => false
				);

			$price = $db->fetch_array_assoc($result);
		}

		// E-mail
		if (strlen($data['user']['email']) && $warning = check_for_warnings("email", $data['user']['email']))
			$warnings['email'] = $warning;

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings)) {
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);
		}

		//
		// Wszystko przebiegło pomyślnie, zwracamy o tym info

		// Pobieramy koszt usługi dla przelewu / paypal / portfela
		$cost_transfer = $heart->get_tariff_provision($data['tariff']);

		$purchase_data = array(
			'service' => $this->service['id'],
			'order' => array(
				'server' => $data['order']['server'],
				'auth_data' => $data['order']['auth_data'],
				'amount' => $price['amount'],
				'forever' => $price['amount'] == -1 ? true : false
			),
			'user' => $data['user'],
			'tariff' => $data['tariff'],
			'cost_transfer' => $cost_transfer
		);

		return array(
			'status' => "validated",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase_data' => $purchase_data
		);
	}

	//
	// Funkcja zakupu
	public function purchase($data)
	{
		// Dodanie informacji o zakupie usługi
		return add_bought_service_info($data['user']['uid'], $data['user']['username'], $data['user']['ip'], $data['transaction']['method'],
			$data['transaction']['payment_id'], $this->service['id'], $data['order']['server'], $data['order']['amount'],
			$data['order']['auth_data'], $data['user']['email']
		);
	}

	//
	// Funkcja wywolywana podczas usuwania uslugi
	public function service_delete($service_id)
	{
		global $db;

		$db->query($db->prepare(
			"ALTER TABLE `" . TABLE_PREFIX . "servers` " .
			"DROP `%s`",
			array($service_id)
		));
	}

}