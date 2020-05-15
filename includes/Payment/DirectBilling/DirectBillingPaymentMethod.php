<?php
namespace App\Payment\DirectBilling;

use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportDirectBilling;

class DirectBillingPaymentMethod implements IPaymentMethod
{
    /** @var Translator */
    private $lang;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var DirectBillingPriceService */
    private $directBillingPriceService;

    public function __construct(
        PaymentModuleManager $paymentModuleManager,
        DirectBillingPriceService $directBillingPriceService,
        TranslationManager $translationManager
    ) {
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
        $this->directBillingPriceService = $directBillingPriceService;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        return $this->directBillingPriceService->getOldAndNewPrice($purchase);
    }

    /**
     * @param Purchase $purchase
     * @return bool
     */
    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING) &&
            $this->directBillingPriceService->getPrice($purchase) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_DIRECT_BILLING);
    }

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return PaymentResult
     * @throws PaymentProcessingException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING)
        );
        $price = $this->directBillingPriceService->getPrice($purchase);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            throw new PaymentProcessingException(
                "direct_billing_unavailable",
                $this->lang->t("direct_billing_unavailable")
            );
        }

        if ($price === null) {
            throw new PaymentProcessingException(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable")
            );
        }

        return $paymentModule->prepareDirectBilling($price, $purchase);
    }
}
