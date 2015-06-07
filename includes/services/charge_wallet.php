<?php

$heart->register_service_module("charge_wallet", "Doładowanie Portfela", "ServiceChargeWallet", "ServiceChargeWalletSimple");

class ServiceChargeWalletSimple extends Service implements IServiceMustBeLogged
{

	const MODULE_ID = "charge_wallet";

}

class ServiceChargeWallet extends ServiceChargeWalletSimple implements IServicePurchase, IServicePurchaseWeb
{

	function __construct($service)
	{
		global $settings, $scripts, $stylesheets;

		// Wywolujemy konstruktor klasy ktora rozszerzamy
		parent::__construct($service);

		// Dodajemy skrypt js
		$scripts[] = "{$settings['shop_url_slash']}jscripts/services/charge_wallet.js?version=" . VERSION;
		// Dodajemy szablon css
		$stylesheets[] = "{$settings['shop_url_slash']}styles/services/charge_wallet.css?version=" . VERSION;
	}

	public function form_purchase_service()
	{
		global $settings, $lang;

		if ($settings['sms_service']) {
			$payment_sms = new Payment($settings['sms_service']);

			// Pobieramy opcję wyboru doładowania za pomocą SMS
			eval("\$option_sms = \"" . get_template("services/charge_wallet/option_sms") . "\";");

			$sms_list = "";
			foreach ($payment_sms->payment_api->sms_list AS $row) {
				$row['cost'] = number_format(get_sms_cost($row['number']) * $settings['vat'], 2);
				$row['provision'] = number_format($row['provision'], 2);
				// Przygotowuje opcje wyboru
				$sms_list .= create_dom_element("option",
					newsprintf($lang['charge_sms_option'], $row['cost'], $settings['currency'], $row['provision'], $settings['currency']),
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

	public function validate_purchase_form($data)
	{
		global $heart, $user, $settings, $lang;

		if (!is_logged()) {
			return array(
				'status' => "not_logged_in",
				'text' => $lang['you_arent_logged'],
				'positive' => false
			);
		}

		// Są tylko dwie metody doładowania portfela
		if (!in_array($data['method'], array("sms", "transfer"))) {
			return array(
				'status' => "wrong_method",
				'text' => $lang['wrong_charge_method'],
				'positive' => false
			);
		}

		$warnings = array();

		if ($data['method'] == "sms") {
			if (!strlen($data['tariff']))
				$warnings['tariff'] .= $lang['charge_amount_not_chosen']."<br />";
		} else if ($data['method'] == "transfer") {
			// Kwota doładowania
			if ($warning = check_for_warnings("number", $data['transfer_amount']))
				$warnings['transfer_amount'] = $warning;
			if ($data['transfer_amount'] <= 1)
				$warnings['transfer_amount'] .= newsprintf($lang['charge_amount_too_low'], "1.00 ".$settings['currency'])."<br />";
		}

		// Jeżeli są jakieś błedy, to je zwróć
		if (!empty($warnings))
			return array(
				'status' => "warnings",
				'text' => $lang['form_wrong_filled'],
				'positive' => false,
				'data' => array('warnings' => $warnings)
			);

		// Zbieramy dane do jednego miejsca, aby potem je zwrócić
		$purchase_data = array(
			'service' => $this->service['id'],
			'user' => array(
				'uid' => $user['uid']
			),
			'tariff' => $data['tariff'],
			'cost_transfer' => $data['transfer_amount'],
			'no_wallet' => true
		);
		if ($data['method'] == "sms") {
			$purchase_data['no_transfer'] = true;
			$purchase_data['order']['amount'] = $heart->get_tariff_provision($data['tariff']);
		} else if ($data['method'] == "transfer") {
			$purchase_data['no_sms'] = true;
			$purchase_data['order']['amount'] = $data['transfer_amount'];
		}

		return array(
			'status' => "validated",
			'text' => $lang['purchase_form_validated'],
			'positive' => true,
			'purchase_data' => $purchase_data
		);
	}

	public function purchase($data)
	{
		if ($this->service === NULL)
			return;

		// Aktualizacja stanu portfela
		$this->charge_wallet($data['user']['uid'], $data['order']['amount']);

		// Dodanie informacji o zakupie usługi
		return add_bought_service_info($data['user']['uid'], $data['user']['username'], $data['user']['ip'], $data['transaction']['method'],
			$data['transaction']['payment_id'], $this->service['id'], 0, $data['order']['amount'], $data['user']['username'], $data['user']['email']);
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
				$desc = newsprintf($lang['wallet_was_charged'], $data['amount']);
				eval("\$output = \"" . get_template("services/charge_wallet/web_purchase_info_sms", 0, 1, 0) . "\";");
			} else if ($data['payment'] == "transfer")
				eval("\$output = \"" . get_template("services/charge_wallet/web_purchase_info_transfer", 0, 1, 0) . "\";");

			return $output;
		} else if ($action == "payment_log") {
			return array(
				'text' => newsprintf($lang['wallet_was_charged'], $data['amount']),
				'class' => "income"
			);
		}
	}

	//
	// Szczegóły zamówienia
	public function order_details($data)
	{
		global $lang, $settings;

		$data['order']['amount'] = number_format($data['order']['amount'], 2);

		eval("\$output = \"" . get_template("services/charge_wallet/order_details", 0, 1, 0) . "\";");
		return $output;
	}

	public function get_short_description()
	{
		return $this->service['description'];
	}

	private function charge_wallet($uid, $amount)
	{
		global $db;
		$db->query($db->prepare(
			"UPDATE `" . TABLE_PREFIX . "users` " .
			"SET `wallet` = `wallet`+'%.2f' " .
			"WHERE `uid` = '%d'",
			array($amount, $uid)
		));
	}

}