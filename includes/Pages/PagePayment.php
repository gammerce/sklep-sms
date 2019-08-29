<?php
namespace App\Pages;

use App\Models\Purchase;
use App\Payment;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceServiceCode;

class PagePayment extends Page
{
    const PAGE_ID = 'payment';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('title_payment');
    }

    protected function content($get, $post)
    {
        // Sprawdzanie hashu danych przesłanych przez formularz
        if (
            !isset($post['sign']) ||
            $post['sign'] != md5($post['data'] . $this->settings['random_key'])
        ) {
            return $this->lang->translate('wrong_sign');
        }

        /** @var Purchase $purchaseData */
        $purchaseData = unserialize(base64_decode($post['data']));

        // Fix: get user data again to avoid bugs linked with user wallet
        $purchaseData->user = $this->heart->getUser($purchaseData->user->getUid());

        if (!($purchaseData instanceof Purchase)) {
            return $this->lang->translate('error_occured');
        }

        if (
            ($service_module = $this->heart->getServiceModule($purchaseData->getService())) ===
                null ||
            !($service_module instanceof IServicePurchaseWeb)
        ) {
            return $this->lang->translate('bad_module');
        }

        // Pobieramy szczegóły zamówienia
        $order_details = $service_module->orderDetails($purchaseData);

        //
        // Pobieramy sposoby płatności

        $payment_methods = '';
        // Sprawdzamy, czy płatność za pomocą SMS jest możliwa
        if (
            $purchaseData->getPayment('sms_service') &&
            $purchaseData->getTariff() !== null &&
            !$purchaseData->getPayment('no_sms')
        ) {
            $payment_sms = new Payment($purchaseData->getPayment('sms_service'));
            $payment_methods .= $this->template->render(
                'payment_method_sms',
                compact('purchaseData', 'payment_sms')
            );
        }

        $cost_transfer =
            $purchaseData->getPayment('cost') !== null
                ? number_format($purchaseData->getPayment('cost') / 100.0, 2)
                : "0.00";

        if (
            strlen($this->settings['transfer_service']) &&
            $purchaseData->getPayment('cost') !== null &&
            $purchaseData->getPayment('cost') > 1 &&
            !$purchaseData->getPayment('no_transfer')
        ) {
            $payment_methods .= $this->template->render(
                "payment_method_transfer",
                compact('cost_transfer')
            );
        }

        if (
            is_logged() &&
            $purchaseData->getPayment('cost') !== null &&
            !$purchaseData->getPayment('no_wallet')
        ) {
            $payment_methods .= $this->template->render(
                "payment_method_wallet",
                compact('cost_transfer')
            );
        }

        if (
            !$purchaseData->getPayment('no_code') &&
            $service_module instanceof IServiceServiceCode
        ) {
            $payment_methods .= $this->template->render("payment_method_code");
        }

        $purchaseData = htmlspecialchars($post['data']);
        $purchase_sign = htmlspecialchars($post['sign']);

        return $this->template->render(
            "payment_form",
            compact('order_details', 'payment_methods', 'purchaseData', 'purchase_sign')
        );
    }
}
