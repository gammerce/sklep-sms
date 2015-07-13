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
		global $settings, $lang;

		if (strlen($settings['sms_service'])) {
			$payment_sms = new Payment($settings['sms_service']);

			// Pobieramy opcję wyboru doładowania za pomocą SMS
			eval("\$option_sms = \"" . get_template("services/charge_wallet/option_sms") . "\";");

			$sms_list = "";
			foreach ($payment_sms->payment_api->sms_list AS $row) {
				$row['cost'] = number_format(get_sms_cost($row['number']) * $settings['vat'], 2);
				$row['provision'] = number_format($row['provision'], 2);
				// Przygotowuje opcje wyboru
				$sms_list .= create_dom_element("option",
					$lang->sprintf($lang->charge_sms_option, $row['cost'], $settings['currency'], $row['provision'], $settings['currency']),
					array(
						'value' => "{$row['tariff']}"
					)
				);
			}
			eval("\$sms_body = \"" . get_template("services/charge_wallet/sms_body") . "\";");
		}

		if ($settings['transfer_service']) {
			// Pobieramy opcję wyboru doładowania za pomocą przelewu
			eval("\$option_transfer = \"" . get_template("services/charge_wallet/option_transfer") . "\";");

			eval("\$transfer_body = \"" . get_template("services/charge_wallet/transfer_body") . "\";");
		}

		eval("\$output = \"" . get_template("services/charge_wallet/purchase_form") . "\";");

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

		$purchase = new Entity_Purchase(array(
			'service' => $this->service['id'],
			'tariff' => $data['tariff'],
			'payment' => array(
				'cost' => $data['transfer_amount'],
				'no_wallet' => true
			)
		));

		if ($data['method'] == "sms") {
			$purchase->setPayment(array(
				'no_transfer' => true
			));
			$purchase->setOrder(array(
				'amount' => $heart->get_tariff_provision($data['tariff'])
			));
		} else if ($data['method'] == "transfer") {
			$purchase->setPayment(array(
				'no_sms' => true
			));
			$purchase->setOrder(array(
				'amount' => $data['transfer_amount']
			));
		}

		return array(
			'status' => "validated",
			'text' => $lang->purchase_form_validated,
			'positive' => true,
			'purchase' => $purchase
		);
	}

	public function purchase($purchase)
	{
		// Aktualizacja stanu portfela
		$this->charge_wallet($purchase->getUser('uid'), $purchase->getOrder('amount'));

		return add_bought_service_info(
			$purchase->getUser('uid'), $purchase->getUser('username'), $purchase->getUser('ip'), $purchase->getPayment('method'),
			$purchase->getPayment('payment_id'), $this->service['id'], 0, $purchase->getOrder('amount'), $purchase->getUser('username'),
			$purchase->getEmail()
		);
	}

	public function purchase_info($action, $data)
	{
		global $heart, $settings, $lang;

		$data['amount'] = number_format($data['amount'], 2) . " " . $settings['currency'];
		$data['cost'] = number_format($data['cost'], 2) . " " . $settings['currency'];

		if ($data['payment'] == "sms") {
			$data['sms_code'] = htmlspecialchars($data['sms_code']);
			$data['sms_text'] = htmlspecialchars($data['sms_text']);
			$data['sms_number'] = htmlspecialchars($data['sms_number']);
		}

		if ($action == "web") {
			if ($data['payment'] == "sms") {
				$desc = $lang->sprintf($lang->wallet_was_charged, $data['amount']);
				eval("\$output = \"" . get_template("services/charge_wallet/web_purchase_info_sms", 0, 1, 0) . "\";");
			} else if ($data['payment'] == "transfer")
				eval("\$output = \"" . get_template("services/charge_wallet/web_purchase_info_transfer", 0, 1, 0) . "\";");

			return $output;
		} else if ($action == "payment_log")
			return array(
				'text' => $lang->sprintf($lang->wallet_was_charged, $data['amount']),
				'class' => "income"
			);
	}

	public function order_details($purchase)
	{
		global $lang, $settings;

		$amount = number_format($purchase->getOrder('amount'), 2);

		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/order_details", 0, 1, 0) . "\";");
		return $output;
	}

	public function description_short_get()
	{
		return $this->service['description'];
	}

	private function charge_wallet($uid, $amount)
	{
		global $db;
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "users` " .
			"SET `wallet` = `wallet` + '%.2f' " .
			"WHERE `uid` = '%d'",
			array($amount, $uid)
		));
	}

}