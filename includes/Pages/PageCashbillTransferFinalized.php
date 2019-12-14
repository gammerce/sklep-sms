<?php
namespace App\Pages;

use App\Models\Purchase;
use App\Verification\Cashbill;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = 'transfer_finalized';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('transfer_finalized');
    }

    protected function content(array $query, array $body)
    {
        /** @var Cashbill $paymentModule */
        $paymentModule = $this->heart->getPaymentModuleOrFail($this->settings['transfer_service']);

        if (
            $paymentModule->checkSign($query, $paymentModule->getKey(), $query['sign']) &&
            $query['service'] != $paymentModule->getService()
        ) {
            return $this->lang->translate('transfer_unverified');
        }

        // prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
        if (strtoupper($query['status']) != 'OK') {
            return $this->lang->translate('transfer_error');
        }

        return purchase_info([
            'payment' => Purchase::METHOD_TRANSFER,
            'payment_id' => $query['orderid'],
            'action' => 'web',
        ]);
    }
}
