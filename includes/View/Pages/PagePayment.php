<?php
namespace App\View\Pages;

use App\Models\Purchase;
use App\Payment\PurchaseSerializer;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;

class PagePayment extends Page
{
    const PAGE_ID = 'payment';

    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var SmsPriceService */
    private $smsPriceService;

    public function __construct(
        PurchaseSerializer $purchaseSerializer,
        PriceTextService $priceTextService,
        SmsPriceService $smsPriceService
    ) {
        parent::__construct();

        $this->purchaseSerializer = $purchaseSerializer;
        $this->priceTextService = $priceTextService;
        $this->heart->pageTitle = $this->title = $this->lang->t('title_payment');
        $this->smsPriceService = $smsPriceService;
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

        $purchase = $this->purchaseSerializer->deserializeAndDecode($body['data']);
        if (!$purchase) {
            return $this->lang->t('error_occurred');
        }

        $serviceModule = $this->heart->getServiceModule($purchase->getService());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t('bad_module');
        }

        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM)
        );

        $transferPrice = $this->priceTextService->getTransferText(
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE)
        );

        $orderDetails = $serviceModule->orderDetails($purchase);

        $paymentMethods = '';

        if ($this->isSmsAvailable($purchase, $smsPaymentModule)) {
            $smsCode = $smsPaymentModule->getSmsCode();
            $smsNumber = $this->smsPriceService->getNumber(
                $purchase->getPrice()->getSmsPrice(),
                $smsPaymentModule
            );
            $priceGross = $this->priceTextService->getSmsGrossText(
                $purchase->getPrice()->getSmsPrice()
            );
            $paymentMethods .= $this->template->render(
                'payment_method_sms',
                compact('priceGross', 'smsCode', 'smsNumber')
            );
        }

        if ($this->isTransferAvailable($purchase)) {
            $paymentMethods .= $this->template->render(
                "payment_method_transfer",
                compact('transferPrice')
            );
        }

        if ($this->isWalletAvailable($purchase)) {
            $paymentMethods .= $this->template->render(
                "payment_method_wallet",
                compact('transferPrice')
            );
        }

        if ($this->isServiceCodeAvailable($purchase, $serviceModule)) {
            $paymentMethods .= $this->template->render("payment_method_code");
        }

        $purchaseData = $body['data'];
        $purchaseSign = $body['sign'];

        return $this->template->render(
            "payment_form",
            compact('orderDetails', 'paymentMethods', 'purchaseData', 'purchaseSign')
        );
    }

    private function isSmsAvailable(Purchase $purchase, PaymentModule $paymentModule = null)
    {
        return $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM) &&
            $purchase->getPrice() &&
            $purchase->getPrice()->hasSmsPrice() &&
            $paymentModule instanceof SupportSms &&
            !$purchase->getPayment(Purchase::PAYMENT_SMS_DISABLED);
    }

    private function isTransferAvailable(Purchase $purchase)
    {
        return $this->settings->getTransferPlatformId() &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) !== null &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) > 1 &&
            !$purchase->getPayment(Purchase::PAYMENT_TRANSFER_DISABLED);
    }

    private function isWalletAvailable(Purchase $purchase)
    {
        return is_logged() &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_WALLET_DISABLED);
    }

    private function isServiceCodeAvailable(Purchase $purchase, ServiceModule $serviceModule)
    {
        return !$purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE_DISABLED) &&
            $serviceModule instanceof IServiceServiceCode;
    }
}
