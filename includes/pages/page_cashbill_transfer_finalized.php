<?php

use App\Payment;
use App\Verification\Cashbill;

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
        $payment = new Payment($this->settings['transfer_service']);
        /** @var Cashbill $paymentModule */
        $paymentModule = $payment->getPaymentModule();

        if ($paymentModule->checkSign($get, $paymentModule->getKey(), $get['sign'])
            && $get['service'] != $paymentModule->getService()) {
            return $this->lang->translate('transfer_unverified');
        }

        // prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
        if (strtoupper($get['status']) != 'OK') {
            return $this->lang->translate('transfer_error');
        }

        return purchase_info([
            'payment'    => 'transfer',
            'payment_id' => $get['orderid'],
            'action'     => 'web',
        ]);
    }
}
