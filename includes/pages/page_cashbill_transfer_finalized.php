<?php

$heart->register_page("transfer_finalized", "PageCashbillTransferFinalized");

class PageCashbillTransferFinalized extends Page
{

	const PAGE_ID = "transfer_finalized";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('transfer_finalized');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $settings, $lang;

		$payment = new Payment($settings['transfer_service']);
		if ($payment->getPaymentModule()->check_sign($get, $payment->getPaymentModule()->getKey(), $get['sign'])
			&& $get['service'] != $payment->getPaymentModule()->getService()
		) {
			return $lang->translate('transfer_unverified');
		}

		// prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
		if (strtoupper($get['status']) != 'OK') {
			return $lang->translate('transfer_error');
		}

		return purchase_info(array(
			'payment' => 'transfer',
			'payment_id' => $get['orderid'],
			'action' => 'web'
		));
	}

}