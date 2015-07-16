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

		/** @var Entity_Purchase $purchase */
		$purchase = unserialize(base64_decode($post['data']));

		if (!($purchase instanceof Entity_Purchase))
			return $lang->error_occured;

		if (($service_module = $heart->get_service_module($purchase->getService())) === NULL
			|| !object_implements($service_module, "IService_PurchaseWeb"))
			return $lang->bad_module;

		// Pobieramy szczegóły zamówienia
		$order_details = $service_module->order_details($purchase);

		//
		// Pobieramy sposoby płatności

		$payment_methods = "";
		// Sprawdzamy, czy płatność za pomocą SMS jest możliwa
		if ($purchase->getPayment("sms_service") && $purchase->getTariff() !== NULL && !$purchase->getPayment('no_sms')) {
			$payment_sms = new Payment($purchase->getPayment("sms_service"));
			if (strlen($number = $payment_sms->get_number_by_tariff($purchase->getTariff()))) {
				$tariff['number'] = $number;
				$tariff['cost'] = number_format(get_sms_cost($tariff['number']) * $settings['vat'], 2);
				eval("\$payment_methods .= \"" . get_template("payment_method_sms") . "\";");
			}
		}

		$cost_transfer = number_format($purchase->getPayment('cost'), 2);
		if ($settings['transfer_service'] && $purchase->getPayment('cost') !== NULL && $purchase->getPayment('cost') > 1 && !$purchase->getPayment('no_transfer'))
			eval("\$payment_methods .= \"" . get_template("payment_method_transfer") . "\";");

		if (is_logged() && $purchase->getPayment('cost') !== NULL && !$purchase->getPayment('no_wallet'))
			eval("\$payment_methods .= \"" . get_template("payment_method_wallet") . "\";");

		if (!$purchase->getPayment('no_code') && object_implements($service_module, "IService_ServiceCode"))
			eval("\$payment_methods .= \"" . get_template("payment_method_code") . "\";");

		$purchase_data = htmlspecialchars($post['data']);
		$purchase_sign = htmlspecialchars($post['sign']);

		eval("\$output = \"" . get_template("payment_form") . "\";");

		return $output;
	}

}