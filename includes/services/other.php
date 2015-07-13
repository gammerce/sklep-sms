<?php

$heart->register_service_module("other", "Inne", "ServiceOther", "ServiceOtherSimple");

class ServiceOtherSimple extends Service implements IService_Create
{

	const MODULE_ID = "other";

	public function service_admin_manage_post($data)
	{
		global $db;

		if ($data['action'] == "service_edit" && $data['id2'] != $data['id'])
			$db->query($db->prepare(
				"UPDATE `" . TABLE_PREFIX . "servers_services` " .
				"SET `service_id` = '%s' " .
				"WHERE `service_id` = '%s'",
				array($data['id'], $data['id2'])
			));
	}

}

class ServiceOther extends ServiceOtherSimple implements IService_Purchase, IService_PurchaseOutside
{

	//
	// Funkcja przygotowania zakupu
	//
	public function purchase_data_validate($purchase)
	{
		global $heart, $db, $lang;

		$warnings = array();

		// Serwer
		$server = array();
		if (!strlen($purchase->getOrder('server')))
			$warnings['server'][] = $lang->must_choose_server;
		else {
			// Sprawdzanie czy serwer o danym id istnieje w bazie
			$server = $heart->get_server($purchase->getOrder('server'));
			if (!$heart->server_service_linked($server['id'], $this->service['id']))
				$warnings['server'][] = $lang->chosen_incorrect_server;
		}

		// Wartość usługi
		$price = array();
		if (!strlen($purchase->getTariff()))
			$warnings['value'][] = $lang->must_choose_amount;
		else {
			// Wyszukiwanie usługi o konkretnej cenie
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
				array($this->service['id'], $purchase->getTariff(), $server['id'])
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
		if (strlen($purchase->getUser('email')) && $warning = check_for_warnings("email", $purchase->getUser('email')))
			$warnings['email'] = array_merge((array)$warnings['email'], $warning);

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings)) {
			return array(
				'status' => "warnings",
				'text' => $lang->form_wrong_filled,
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);
		}

		$purchase->setOrder(array(
			'amount' => $price['amount'],
			'forever' => $price['amount'] == -1 ? true : false
		));

		$purchase->setPayment(array(
			'cost' => $heart->get_tariff_provision($purchase->getTariff())
		));

		return array(
			'status' => "validated",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase' => $purchase
		);
	}

	public function purchase($purchase)
	{
		return add_bought_service_info(
			$purchase->getUser('uid'), $purchase->getUser('username'), $purchase->getUser('ip'), $purchase->getPayment('method'),
			$purchase->getPayment('payment_id'), $this->service['id'], $purchase->getOrder('server'), $purchase->getOrder('amount'),
			$purchase->getOrder('auth_data'), $purchase->getUser('email')
		);
	}

	public function service_delete($service_id)
	{
		global $db;

		$db->query($db->prepare(
			"DELETE FROM `" . TABLE_PREFIX . "servers_services` " .
			"WHERE `service_id` = '%s'",
			array($service_id)
		));
	}

}