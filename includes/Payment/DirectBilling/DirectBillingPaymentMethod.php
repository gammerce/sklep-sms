<?php
namespace App\Payment\DirectBilling;

use App\Models\Purchase;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Managers\PaymentModuleManager;

class DirectBillingPaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Translator */
    private $lang;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        PaymentModuleManager $paymentModuleManager,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
        $this->purchaseDataService = $purchaseDataService;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function render(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING)
        );
        return $this->template->render("payment/payment_method_direct_billing", compact("price"));
    }

    /**
     * @param Purchase $purchase
     * @return bool
     */
    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_DIRECT_BILLING);
    }

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return Result
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING)
        );

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING) === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t('payment_method_unavailable'),
                false
            );
        }

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new Result(
                "direct_billing_unavailable",
                $this->lang->t('direct_billing_unavailable'),
                false
            );
        }

        $fileName = $this->purchaseDataService->storePurchase($purchase);

        return $paymentModule->prepareDirectBilling($purchase, $fileName);
    }
}
