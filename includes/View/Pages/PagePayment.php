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

        /** @var Purchase $purchaseData */
        $purchaseData = unserialize(base64_decode($body['data']));

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchaseData->user = $this->heart->getUser($purchaseData->user->getUid());

        if (!($purchaseData instanceof Purchase)) {
            return $this->lang->t('error_occurred');
        }

        if (
            ($serviceModule = $this->heart->getServiceModule($purchaseData->getService())) ===
                null ||
            !($serviceModule instanceof IServicePurchaseWeb)
        ) {
            return $this->lang->t('bad_module');
        }

        $orderDetails = $serviceModule->orderDetails($purchaseData);

        $paymentMethods = '';
        // Check if it's possible to pay using SMS
        if (
            $purchaseData->getPayment('sms_platform') &&
            $purchaseData->getTariff() !== null &&
            !$purchaseData->getPayment('no_sms')
        ) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
                $purchaseData->getPayment('sms_platform')
            );

            if ($paymentModule instanceof SupportSms) {
                $smsCode = $paymentModule->getSmsCode();
                $paymentMethods .= $this->template->render(
                    'payment_method_sms',
                    compact('purchaseData', 'smsCode')
                );
            }
        }

        $costTransfer =
            $purchaseData->getPayment('cost') !== null
                ? number_format($purchaseData->getPayment('cost') / 100.0, 2)
                : "0.00";

        if (
            $this->settings->getTransferPlatformId() &&
            $purchaseData->getPayment('cost') !== null &&
            $purchaseData->getPayment('cost') > 1 &&
            !$purchaseData->getPayment('no_transfer')
        ) {
            $paymentMethods .= $this->template->render(
                "payment_method_transfer",
                compact('costTransfer')
            );
        }

        if (
            is_logged() &&
            $purchaseData->getPayment('cost') !== null &&
            !$purchaseData->getPayment('no_wallet')
        ) {
            $paymentMethods .= $this->template->render(
                "payment_method_wallet",
                compact('costTransfer')
            );
        }

        if (
            !$purchaseData->getPayment('no_code') &&
            $serviceModule instanceof IServiceServiceCode
        ) {
            $paymentMethods .= $this->template->render("payment_method_code");
        }

        $purchaseData = $body['data'];
        $purchaseSign = $body['sign'];

        return $this->template->render(
            "payment_form",
            compact('orderDetails', 'paymentMethods', 'purchaseData', 'purchaseSign')
        );
    }
}
