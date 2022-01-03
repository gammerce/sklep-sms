<?php
namespace App\Payment\Transfer;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Loggers\FileLogger;
use App\Managers\ServiceModuleManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\PurchaseDataService;
use App\Payment\Invoice\InvoiceIssueException;
use App\Payment\Invoice\InvoiceService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;

class TransferPaymentService
{
    private PaymentTransferRepository $paymentTransferRepository;
    private DatabaseLogger $logger;
    private PurchaseDataService $purchaseDataService;
    private ServiceModuleManager $serviceModuleManager;
    private TransferPriceService $transferPriceService;
    private InvoiceService $invoiceService;
    private FileLogger $fileLogger;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        TransferPriceService $transferPriceService,
        InvoiceService $invoiceService,
        DatabaseLogger $logger,
        FileLogger $fileLogger
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->logger = $logger;
        $this->purchaseDataService = $purchaseDataService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->transferPriceService = $transferPriceService;
        $this->invoiceService = $invoiceService;
        $this->fileLogger = $fileLogger;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @return int
     * @throws InvalidPaidAmountException
     * @throws PaymentRejectedException
     * @throws InvalidServiceModuleException
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment): int
    {
        if (!$finalizedPayment->isSuccessful()) {
            throw new PaymentRejectedException();
        }

        if (
            $finalizedPayment->getCost()->notEqual($this->transferPriceService->getPrice($purchase))
        ) {
            throw new InvalidPaidAmountException();
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        $paymentTransfer = $this->paymentTransferRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome()->asInt(),
            $finalizedPayment->getCost()->asInt(),
            $finalizedPayment->getExternalServiceId(),
            $purchase->getAddressIp(),
            $purchase->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentTransfer->getId(),
        ]);

        $boughtServiceId = $serviceModule->purchase($purchase);

        if (!$finalizedPayment->isTestMode()) {
            try {
                $this->invoiceService->create(
                    $purchase->getBillingAddress(),
                    $finalizedPayment->getCost(),
                    $serviceModule->service->getName()
                );
            } catch (InvoiceIssueException $e) {
                $this->fileLogger->error("Couldn't issue an invoice {$e->getMessage()}");
            }
        }

        $this->logger->logWithUser(
            $purchase->user,
            "log_external_payment_accepted",
            $purchase->getPaymentOption()->getPaymentMethod(),
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost(),
            $finalizedPayment->getExternalServiceId()
        );

        $this->purchaseDataService->deletePurchase($purchase);

        return $boughtServiceId;
    }
}
