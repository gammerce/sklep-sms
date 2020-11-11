<?php
namespace App\Payment\Transfer;

use App\Managers\PaymentModuleManager;
use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Support\Money;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentMethod implements IPaymentMethod
{
    /** @var Translator */
    private $lang;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var TransferPaymentService */
    private $transferPaymentService;

    /** @var TransferPriceService */
    private $transferPriceService;

    public function __construct(
        TranslationManager $translationManager,
        TransferPaymentService $transferPaymentService,
        TransferPriceService $transferPriceService,
        PaymentModuleManager $paymentModuleManager
    ) {
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
        $this->transferPaymentService = $transferPaymentService;
        $this->transferPriceService = $transferPriceService;
    }

    public function getPaymentDetails(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        return $this->transferPriceService->getOldAndNewPrice($purchase);
    }

    public function isAvailable(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
        $price = $this->transferPriceService->getPrice($purchase);
        return $paymentModule instanceof SupportTransfer && $price !== null;
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

        $price = $this->transferPriceService->getPrice($purchase);

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

        if ($price->equal(0)) {
            return $this->makeSyncPayment($purchase);
        } else {
            return $this->makeAsyncPayment($paymentModule, $price, $purchase);
        }
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

        $boughtServiceId = $this->transferPaymentService->finalizePurchase(
            $purchase,
            $finalizedPayment
        );

        return new PaymentResult(PaymentResultType::PURCHASED(), $boughtServiceId);
    }

    /**
     * @param SupportTransfer $paymentModule
     * @param Money $price
     * @param Purchase $purchase
     * @return PaymentResult
     * @throws PaymentProcessingException
     */
    private function makeAsyncPayment(
        SupportTransfer $paymentModule,
        Money $price,
        Purchase $purchase
    ) {
        $data = $paymentModule->prepareTransfer($price->asInt(), $purchase);
        return new PaymentResult(PaymentResultType::EXTERNAL(), $data);
    }
}
