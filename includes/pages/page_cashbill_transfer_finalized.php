<?php

use App\Payment;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = 'transfer_finalized';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('transfer_finalized');
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

        return purchase_info([
            'payment'    => 'transfer',
            'payment_id' => $get['orderid'],
            'action'     => 'web',
        ]);
    }
}
