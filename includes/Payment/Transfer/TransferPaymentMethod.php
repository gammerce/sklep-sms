<?php
namespace App\Payment\Transfer;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Payment\Interfaces\IPaymentMethod;
use App\PromoCode\PromoCodeService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentMethod implements IPaymentMethod
{
    /** @var PriceTextService */
    private $priceTextService;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var PromoCodeService */
    private $promoCodeService;

    /** @var TransferPaymentService */
    private $transferPaymentService;

    public function __construct(
        ServiceManager $serviceManager,
        PriceTextService $priceTextService,
        PromoCodeService $promoCodeService,
        TranslationManager $translationManager,
        TransferPaymentService $transferPaymentService,
        PaymentModuleManager $paymentModuleManager,
        Settings $settings
    ) {
        $this->priceTextService = $priceTextService;
        $this->serviceManager = $serviceManager;
        $this->lang = $translationManager->user();
        $this->settings = $settings;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->promoCodeService = $promoCodeService;
        $this->transferPaymentService = $transferPaymentService;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
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

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) !== null &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) > 1 &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_TRANSFER);
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
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER)
        );

        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
        $promoCode = $purchase->getPromoCode();

        if (!($paymentModule instanceof SupportTransfer)) {
            throw new PaymentProcessingException(
                "transfer_unavailable",
                $this->lang->t("transfer_unavailable")
            );
        }

        if ($price === null) {
            throw new PaymentProcessingException(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable")
            );
        }

        if ($promoCode) {
            $price = $this->promoCodeService->applyDiscount($promoCode, $price);
        }

        $service = $this->serviceManager->getService($purchase->getServiceId());
        $purchase->setDesc($this->lang->t("payment_for_service", $service->getNameI18n()));

        if ($price === 0) {
            return $this->makeSyncPayment($purchase);
        } else {
            return $this->makeAsyncPayment($paymentModule, $price, $purchase);
        }
    }

    private function makeSyncPayment(Purchase $purchase)
    {
        // TODO Test it
        $finalizedPayment = (new FinalizedPayment())
            ->setStatus(true)
            ->setOrderId(generate_id(16))
            ->setCost(0)
            ->setIncome(0)
            ->setTransactionId($purchase->getId())
            ->setExternalServiceId("promo_code")
            ->setOutput("OK");

        $boughtServiceId = $this->transferPaymentService->finalizePurchase(
            $purchase,
            $finalizedPayment
        );

        return new PaymentResult(PaymentResultType::PURCHASED(), $boughtServiceId);
    }

    private function makeAsyncPayment(SupportTransfer $paymentModule, $price, Purchase $purchase)
    {
        $data = $paymentModule->prepareTransfer($price, $purchase);
        return new PaymentResult(PaymentResultType::EXTERNAL(), $data);
    }
}
