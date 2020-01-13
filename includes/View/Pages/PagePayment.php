<?php
namespace App\View\Pages;

use App\Models\Purchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\Verification\Abstracts\SupportSms;

class PagePayment extends Page
{
    const PAGE_ID = 'payment';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('title_payment');
    }

    protected function content(array $query, array $body)
    {
        // Check form sign
        if (
            !isset($body['sign']) ||
            $body['sign'] != md5($body['data'] . $this->settings->getSecret())
        ) {
            return $this->lang->t('wrong_sign');
        }

        /** @var Purchase $purchase */
        $purchase = unserialize(base64_decode($body['data']));

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->heart->getUser($purchase->user->getUid());

        if (!($purchase instanceof Purchase)) {
            return $this->lang->t('error_occurred');
        }

        if (
            ($serviceModule = $this->heart->getServiceModule($purchase->getService())) === null ||
            !($serviceModule instanceof IServicePurchaseWeb)
        ) {
            return $this->lang->t('bad_module');
        }

        $orderDetails = $serviceModule->orderDetails($purchase);

        $paymentMethods = '';
        if ($this->isSmsAvailable($purchase)) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
                $purchase->getPayment('sms_platform')
            );

            if ($paymentModule instanceof SupportSms) {
                $smsCode = $paymentModule->getSmsCode();
                $paymentMethods .= $this->template->render(
                    'payment_method_sms',
                    compact('purchase', 'smsCode')
                );
            }
        }

        $costTransfer =
            $purchase->getPayment('cost') !== null
                ? number_format($purchase->getPayment('cost') / 100.0, 2)
                : "0.00";

        if ($this->isTransferAvailable($purchase)) {
            $paymentMethods .= $this->template->render(
                "payment_method_transfer",
                compact('costTransfer')
            );
        }

        if (
            is_logged() &&
            $purchase->getPayment('cost') !== null &&
            !$purchase->getPayment('no_wallet')
        ) {
            $paymentMethods .= $this->template->render(
                "payment_method_wallet",
                compact('costTransfer')
            );
        }

        if (!$purchase->getPayment('no_code') && $serviceModule instanceof IServiceServiceCode) {
            $paymentMethods .= $this->template->render("payment_method_code");
        }

        $purchaseData = $body['data'];
        $purchaseSign = $body['sign'];

        return $this->template->render(
            "payment_form",
            compact('orderDetails', 'paymentMethods', 'purchaseData', 'purchaseSign')
        );
    }

    private function isSmsAvailable(Purchase $purchase)
    {
        return $purchase->getPayment('sms_platform') &&
            $purchase->getPrice() &&
            $purchase->getPrice()->hasSmsPrice() &&
            !$purchase->getPayment('no_sms');
    }

    private function isTransferAvailable(Purchase $purchase)
    {
        return // TODO Ensure below is required
            $this->settings->getTransferPlatformId() &&
                $purchase->getPayment('cost') !== null &&
                $purchase->getPayment('cost') > 1 &&
                $purchase->getPrice() &&
                $purchase->getPrice()->hasTransferPrice() &&
                !$purchase->getPayment('no_transfer');
    }
}
