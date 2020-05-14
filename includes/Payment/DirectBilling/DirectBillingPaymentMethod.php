<?php
namespace App\Payment\DirectBilling;

use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\PromoCode\PromoCodeService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportDirectBilling;

class DirectBillingPaymentMethod implements IPaymentMethod
{
    /** @var PriceTextService */
    private $priceTextService;

    /** @var Translator */
    private $lang;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var PromoCodeService */
    private $promoCodeService;

    public function __construct(
        PriceTextService $priceTextService,
        PaymentModuleManager $paymentModuleManager,
        PromoCodeService $promoCodeService,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager
    ) {
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
        $this->purchaseDataService = $purchaseDataService;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->promoCodeService = $promoCodeService;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING);
        $promoCode = $purchase->getPromoCode();

        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            return [
                "price" => $this->priceTextService->getPriceText($discountedPrice),
                "old_price" => $this->priceTextService->getPlainPrice($price),
            ];
        }

        return [
            "price" => $this->priceTextService->getPriceText($price),
        ];
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
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING);
        $promoCode = $purchase->getPromoCode();

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new Result(
                "direct_billing_unavailable",
                $this->lang->t("direct_billing_unavailable"),
                false
            );
        }

        if ($price === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable"),
                false
            );
        }

        if ($promoCode) {
            $price = $this->promoCodeService->applyDiscount($promoCode, $price);
        }

        return $paymentModule->prepareDirectBilling($price, $purchase);
    }
}
