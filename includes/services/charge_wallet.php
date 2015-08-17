<?php

$heart->register_service_module("charge_wallet", "Doładowanie Portfela", "ServiceChargeWallet", "ServiceChargeWalletSimple");

class ServiceChargeWalletSimple extends Service implements I_BeLoggedMust
{

	const MODULE_ID = "charge_wallet";

}

class ServiceChargeWallet extends ServiceChargeWalletSimple implements IService_Purchase, IService_PurchaseWeb
{

	public function purchase_form_get()
	{
		global $settings, $lang, $templates;

		if (strlen($settings['sms_service'])) {
			$payment_sms = new Payment($settings['sms_service']);

			// Pobieramy opcję wyboru doładowania za pomocą SMS
			$option_sms = eval($templates->render("services/" . $this::MODULE_ID . "/option_sms"));

			$sms_list = "";
			foreach ($payment_sms->payment_api->sms_list AS $row) {
				$row['cost'] = number_format(get_sms_cost($row['number']) * $settings['vat'] / 100.0, 2);
				$row['provision'] = number_format($row['provision'] / 100.0, 2);
				// Przygotowuje opcje wyboru
				$sms_list .= create_dom_element("option",
					$lang->sprintf($lang->charge_sms_option, $row['cost'], $settings['currency'], $row['provision'], $settings['currency']),
					array(
						'value' => "{$row['tariff']}"
					)
				);
			}
			$sms_body = eval($templates->render("services/" . $this::MODULE_ID . "/sms_body"));
		}

		if (strlen($settings['transfer_service'])) {
			// Pobieramy opcję wyboru doładowania za pomocą przelewu
			$option_transfer = eval($templates->render("services/" . $this::MODULE_ID . "/option_transfer"));

			$transfer_body = eval($templates->render("services/" . $this::MODULE_ID . "/transfer_body"));
		}

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/purchase_form"));

		return $output;
	}

	public function purchase_form_validate($data)
	{
		global $heart, $settings, $lang;

		if (!is_logged())
			return array(
				'status' => "not_logged_in",
				'text' => $lang->you_arent_logged,
				'positive' => false
			);

		// Są tylko dwie metody doładowania portfela
		if (!in_array($data['method'], array("sms", "transfer")))
			return array(
				'status' => "wrong_method",
				'text' => $lang->wrong_charge_method,
				'positive' => false
			);

		$warnings = array();

		if ($data['method'] == "sms") {
			if (!strlen($data['tariff']))
				$warnings['tariff'][] = $lang->charge_amount_not_chosen;
		} else if ($data['method'] == "transfer") {
			// Kwota doładowania
			if ($warning = check_for_warnings("number", $data['transfer_amount']))
				$warnings['transfer_amount'] = array_merge((array)$warnings['transfer_amount'], $warning);
			if ($data['transfer_amount'] <= 1)
				$warnings['transfer_amount'][] = $lang->sprintf($lang->charge_amount_too_low, "1.00 " . $settings['currency']);
		}

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
		$purchase_data->setTariff($data['tariff']);
		$purchase_data->setPayment(array(
			'cost' => $data['transfer_amount'],
			'no_wallet' => true
		));

		if ($data['method'] == "sms") {
			$purchase_data->setPayment(array(
				'no_transfer' => true
			));
			$purchase_data->setOrder(array(
				'amount' => $heart->get_tariff_provision($data['tariff'])
			));
		} else if ($data['method'] == "transfer") {
			$purchase_data->setPayment(array(
				'no_sms' => true
			));
			$purchase_data->setOrder(array(
				'amount' => $data['transfer_amount']
			));
		}

		return array(
			'status' => "ok",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase' => $purchase_data
		);
	}

	public function purchase($purchase_data)
	{
		// Aktualizacja stanu portfela
		$this->charge_wallet($purchase_data->user->getUid(), $purchase_data->getOrder('amount'));

		return add_bought_service_info(
			$purchase_data->user->getUid(), $purchase_data->user->getUsername(), $purchase_data->user->getLastip(),
			$purchase_data->getPayment('method'), $purchase_data->getPayment('payment_id'), $this->service['id'], 0,
			$purchase_data->getOrder('amount'), $purchase_data->user->getUsername(), $purchase_data->getEmail()
		);
	}

	public function purchase_info($action, $data)
	{
		global $heart, $settings, $lang, $templates;

		$data['amount'] = number_format($data['amount'] / 100.0, 2) . " " . $settings['currency'];
		$data['cost'] = number_format($data['cost'] / 100.0, 2) . " " . $settings['currency'];

		if ($data['payment'] == "sms") {
			$data['sms_code'] = htmlspecialchars($data['sms_code']);
			$data['sms_text'] = htmlspecialchars($data['sms_text']);
			$data['sms_number'] = htmlspecialchars($data['sms_number']);
		}

		if ($action == "web") {
			if ($data['payment'] == "sms") {
				$desc = $lang->sprintf($lang->wallet_was_charged, $data['amount']);
				$output = eval($templates->render("services/" . $this::MODULE_ID . "/web_purchase_info_sms", true, false));
			} else if ($data['payment'] == "transfer")
				$output = eval($templates->render("services/" . $this::MODULE_ID . "/web_purchase_info_transfer", true, false));

			return $output;
		} else if ($action == "payment_log")
			return array(
				'text' => $lang->sprintf($lang->wallet_was_charged, $data['amount']),
				'class' => "income"
			);
	}

	public function order_details($purchase_data)
	{
		global $lang, $settings, $templates;

		$amount = number_format($purchase_data->getOrder('amount'), 2);

		$output = eval($templates->render("services/" . $this::MODULE_ID . "/order_details", true, false));
		return $output;
	}

	public function description_short_get()
	{
		return $this->service['description'];
	}

	/**
	 * @param int $uid
	 * @param int $amount
	 */
	private function charge_wallet($uid, $amount)
	{
		global $db;
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "users` " .
			"SET `wallet` = `wallet` + '%d' " .
			"WHERE `uid` = '%d'",
			array($amount, $uid)
		));
	}

}