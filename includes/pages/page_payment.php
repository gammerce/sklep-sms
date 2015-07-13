<?php

$heart->register_page("payment", "PagePayment");

class PagePayment extends Page
{

	const PAGE_ID = "payment";

	function __construct()
	{
		global $lang;
		$this->title = $lang->title_payment;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $settings, $lang;

		// Sprawdzanie hashu danych przesłanych przez formularz
		if (!isset($post['sign']) || $post['sign'] != md5($post['data'] . $settings['random_key']))
			return $lang->wrong_sign;

		global $heart;

		/** Odczytujemy dane, ich format powinien być taki jak poniżej
		 * @param array $data 'service',
		 *                        'order'
		 *                            ...
		 *                        'user',
		 *                            'uid',
		 *                            'email'
		 *                            ...
		 *                        'payment_sms'
		 *                        'tariff',
		 *                        'cost_transfer'
		 *                        'no_sms'
		 *                        'no_transfer'
		 *                        'no_wallet'
		 * 						  'no_code'
		 */
		$data = json_decode(base64_decode($post['data']), true);


		if (($service_module = $heart->get_service_module($data['service'])) === NULL
			|| !object_implements($service_module, "IService_PurchaseWeb"))
			return $lang->bad_module;

		// Pobieramy szczegóły zamówienia
		$order_details = $service_module->order_details($data);

		// Pobieramy płatność sms
		$sms_service = if_strlen($data['sms_service'], $settings['sms_service']);

		//
		// Pobieramy sposoby płatności

		$payment_methods = "";
		if ($sms_service && isset($data['tariff']) && !$data['no_sms']) { // Sprawdzamy, czy płatność za pomocą SMS jest możliwa
			$payment_sms = new Payment($sms_service);
			if (strlen($number = $payment_sms->get_number_by_tariff($data['tariff']))) {
				$tariff['number'] = $number;
				$tariff['cost'] = number_format(get_sms_cost($tariff['number']) * $settings['vat'], 2);
				eval("\$payment_methods .= \"" . get_template("payment_method_sms") . "\";");
			}
		}

		$cost_transfer = number_format($data['cost_transfer'], 2);
		if ($settings['transfer_service'] && isset($data['cost_transfer']) && $data['cost_transfer'] > 1 && !$data['no_transfer'])
			eval("\$payment_methods .= \"" . get_template("payment_method_transfer") . "\";");

		if (is_logged() && isset($data['cost_transfer']) && !$data['no_wallet'])
			eval("\$payment_methods .= \"" . get_template("payment_method_wallet") . "\";");

		if (!$data['no_code'] && object_implements($service_module, "IService_ServiceCode"))
			eval("\$payment_methods .= \"" . get_template("payment_method_code") . "\";");

		$purchase_data = htmlspecialchars($post['data']);
		$purchase_sign = htmlspecialchars($post['sign']);

		eval("\$output = \"" . get_template("payment_form") . "\";");

		return $output;
	}

}