<?php

$heart->register_service_module("other", "Inne", "ServiceOther", "ServiceOtherSimple");

class ServiceOtherSimple extends Service implements IService_Create, IService_AdminManage, IService_AvailableOnServers
{

	const MODULE_ID = "other";

	public function service_admin_manage_post($data)
	{
		return array();
	}

	public function service_admin_extra_fields_get()
	{
		return '';
	}

	public function service_admin_manage_pre($data)
	{
		return array();
	}
}

class ServiceOther extends ServiceOtherSimple implements IService_Purchase, IService_PurchaseOutside
{

	public function purchase_data_validate($purchase_data)
	{
		global $heart, $db, $lang;

		$warnings = array();

		// Serwer
		$server = array();
		if (!strlen($purchase_data->getOrder('server')))
			$warnings['server'][] = $lang->must_choose_server;
		else {
			// Sprawdzanie czy serwer o danym id istnieje w bazie
			$server = $heart->get_server($purchase_data->getOrder('server'));
			if (!$heart->server_service_linked($server['id'], $this->service['id']))
				$warnings['server'][] = $lang->chosen_incorrect_server;
		}

		// Wartość usługi
		$price = array();
		if (!strlen($purchase_data->getTariff()))
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

		// E-mail
		if (strlen($purchase_data->getEmail()) && $warning = check_for_warnings("email", $purchase_data->getEmail()))
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

		$purchase_data->setOrder(array(
			'amount' => $price['amount'],
			'forever' => $price['amount'] == -1 ? true : false
		));

		$purchase_data->setPayment(array(
			'cost' => $heart->get_tariff_provision($purchase_data->getTariff())
		));

		return array(
			'status' => "ok",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase' => $purchase_data
		);
	}

	public function purchase($purchase_data)
	{
		return add_bought_service_info(
			$purchase_data->user->getUid(), $purchase_data->user->getUsername(), $purchase_data->user->getLastip(), $purchase_data->getPayment('method'),
			$purchase_data->getPayment('payment_id'), $this->service['id'], $purchase_data->getOrder('server'), $purchase_data->getOrder('amount'),
			$purchase_data->getOrder('auth_data'), $purchase_data->getEmail()
		);
	}
}