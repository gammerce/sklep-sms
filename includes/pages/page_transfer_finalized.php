<?php

$heart->register_page("transfer_finalized", "PageTransferFinalized");

class PageTransferFinalized extends Page
{

	function __construct()
	{
		global $lang;
		$this->title = $lang->transfer_finalized;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $settings, $lang;

		$payment = new Payment($settings['transfer_service']);
		if ($payment->payment_api->check_sign($get, $payment->payment_api->data['key'], $get['sign']) && $get['service'] != $payment->payment_api->data['service'])
			return $lang->transfer_unverified;

		// prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
		if (strtoupper($get['status']) != 'OK')
			return $lang->transfer_error;

		$orderid = htmlspecialchars($get['orderid']);
		$amount = number_format($get['amount'], 2);

		return purchase_info(array(
			'payment' => 'transfer',
			'payment_id' => $get['orderid'],
			'action' => 'web'
		));
	}

}