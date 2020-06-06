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
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentMethod implements IPaymentMethod
{
    /** @var ServiceManager */
    private $serviceManager;

    /** @var Translator */
    private $lang;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var TransferPaymentService */
    private $transferPaymentService;

    /** @var TransferPriceService */
    private $transferPriceService;

    public function __construct(
        ServiceManager $serviceManager,
        TranslationManager $translationManager,
        TransferPaymentService $transferPaymentService,
        TransferPriceService $transferPriceService,
        PaymentModuleManager $paymentModuleManager
    ) {
        $this->serviceManager = $serviceManager;
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
        $this->transferPaymentService = $transferPaymentService;
        $this->transferPriceService = $transferPriceService;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        return $this->transferPriceService->getOldAndNewPrice($purchase);
    }

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM) &&
            $this->transferPriceService->getPrice($purchase) !== null &&
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
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM)
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

        $service = $this->serviceManager->getService($purchase->getServiceId());
        $purchase->setDescription($this->lang->t("payment_for_service", $service->getNameI18n()));

        if ($price === 0) {
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

    private function makeAsyncPayment(SupportTransfer $paymentModule, $price, Purchase $purchase)
    {
        $data = $paymentModule->prepareTransfer($price, $purchase);
        return new PaymentResult(PaymentResultType::EXTERNAL(), $data);
    }
}
