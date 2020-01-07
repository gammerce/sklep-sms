<?php
namespace App\Pages;

use App\Exceptions\InvalidConfigException;
use App\Models\Purchase;
use App\Verification\PaymentModules\Cashbill;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = 'transfer_finalized';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('transfer_finalized');
    }

    protected function content(array $query, array $body)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
            $this->settings->getTransferPlatformId()
        );

        if (!($paymentModule instanceof Cashbill)) {
            throw new InvalidConfigException(
                "Invalid payment platform in shop settings [{$this->settings->getTransferPlatformId()}]."
            );
        }

        if (
            $paymentModule->checkSign($query, $paymentModule->getKey(), $query['sign']) &&
            $query['service'] != $paymentModule->getService()
        ) {
            return $this->lang->t('transfer_unverified');
        }

        // prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
        if (strtoupper($query['status']) != 'OK') {
            return $this->lang->t('transfer_error');
        }

        return purchase_info([
            'payment' => Purchase::METHOD_TRANSFER,
            'payment_id' => $query['orderid'],
            'action' => 'web',
        ]);
    }
}
