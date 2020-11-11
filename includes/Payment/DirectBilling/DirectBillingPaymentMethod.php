<?php
namespace App\Payment\DirectBilling;

use App\Managers\PaymentModuleManager;
use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
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

    /** @var DirectBillingPaymentService */
    private $directBillingPaymentService;

    public function __construct(
        PaymentModuleManager $paymentModuleManager,
        DirectBillingPriceService $directBillingPriceService,
        DirectBillingPaymentService $directBillingPaymentService,
        TranslationManager $translationManager
    ) {
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
        $this->directBillingPriceService = $directBillingPriceService;
        $this->directBillingPaymentService = $directBillingPaymentService;
    }

    public function getPaymentDetails(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        return $this->directBillingPriceService->getOldAndNewPrice($purchase);
    }

    /**
     * @param Purchase $purchase
     * @param PaymentPlatform|null $paymentPlatform
     * @return bool
     */
    public function isAvailable(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
        $price = $this->directBillingPriceService->getPrice($purchase);
        return $paymentModule instanceof SupportDirectBilling && $price !== null;
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
            $purchase->getPaymentOption()->getPaymentPlatformId()
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

        if ($price->equal(0)) {
            return $this->makeSyncPayment($purchase);
        }

        return $paymentModule->prepareDirectBilling($price->asInt(), $purchase);
    }

    private function makeSyncPayment(Purchase $purchase)
    {
        $finalizedPayment = (new FinalizedPayment())
            ->setStatus(true)
            ->setOrderId(generate_id(8))
            ->setCost(0)
            ->setIncome(0)
            ->setTransactionId($purchase->getId())
            ->setExternalServiceId("promo_code")
            ->setTestMode(false);

        $boughtServiceId = $this->directBillingPaymentService->finalizePurchase(
            $purchase,
            $finalizedPayment
        );

        return new PaymentResult(PaymentResultType::PURCHASED(), $boughtServiceId);
    }
}
