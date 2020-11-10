<?php
namespace App\Payment\Transfer;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\PurchaseDataService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;

class TransferPaymentService
{
    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var DatabaseLogger */
    private $logger;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var TransferPriceService */
    private $transferPriceService;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        TransferPriceService $transferPriceService,
        DatabaseLogger $logger
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->logger = $logger;
        $this->purchaseDataService = $purchaseDataService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->transferPriceService = $transferPriceService;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @return int
     * @throws InvalidPaidAmountException
     * @throws PaymentRejectedException
     * @throws InvalidServiceModuleException
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment)
    {
        if (!$finalizedPayment->isSuccessful()) {
            throw new PaymentRejectedException();
        }

        if ($finalizedPayment->getCost() !== $this->transferPriceService->getPrice($purchase)) {
            throw new InvalidPaidAmountException();
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        $paymentTransfer = $this->paymentTransferRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome(),
            $finalizedPayment->getCost(),
            $finalizedPayment->getExternalServiceId(),
            $purchase->user->getLastIp(),
            $purchase->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentTransfer->getId(),
        ]);

        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            "log_external_payment_accepted",
            $purchase->getPaymentOption()->getPaymentMethod(),
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost() / 100,
            $finalizedPayment->getExternalServiceId()
        );

        $this->purchaseDataService->deletePurchase($purchase);

        return $boughtServiceId;
    }
}
