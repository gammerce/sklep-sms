<?php
namespace App\Payment\Transfer;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Payment\General\PurchaseDataService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\System\Heart;

class TransferPaymentService
{
    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    /** @var DatabaseLogger */
    private $logger;

    /** @var Heart */
    private $heart;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ExternalPaymentService $externalPaymentService,
        Heart $heart,
        PurchaseDataService $purchaseDataService,
        DatabaseLogger $logger
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->externalPaymentService = $externalPaymentService;
        $this->heart = $heart;
        $this->logger = $logger;
        $this->purchaseDataService = $purchaseDataService;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @throws InvalidPaidAmountException
     * @throws PaymentRejectedException
     * @throws InvalidServiceModuleException
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment)
    {
        if (!$finalizedPayment->isSuccessful()) {
            throw new PaymentRejectedException();
        }

        if (
            $finalizedPayment->getCost() !== $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        ) {
            throw new InvalidPaidAmountException();
        }

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        $paymentTransfer = $this->paymentTransferRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome(),
            $finalizedPayment->getExternalServiceId(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentTransfer->getId(),
        ]);

        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            'log_external_payment_accepted',
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost() / 100,
            $finalizedPayment->getExternalServiceId()
        );

        $this->purchaseDataService->deletePurchase($finalizedPayment->getDataFilename());
    }
}
