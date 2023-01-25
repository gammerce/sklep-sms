<?php
namespace App\Payment\Transfer;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Loggers\FileLogger;
use App\Managers\ServiceModuleManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\PurchaseDataService;
use App\Payment\Invoice\InvoiceException;
use App\Payment\Invoice\InvoiceService;
use App\Payment\Invoice\InvoiceServiceUnavailableException;
use App\Payment\Invoice\PurchaseItem;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;

class TransferPaymentService
{
    private PaymentTransferRepository $paymentTransferRepository;
    private DatabaseLogger $logger;
    private FileLogger $fileLogger;
    private PurchaseDataService $purchaseDataService;
    private ServiceModuleManager $serviceModuleManager;
    private TransferPriceService $transferPriceService;
    private InvoiceService $invoiceService;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        TransferPriceService $transferPriceService,
        InvoiceService $invoiceService,
        FileLogger $fileLogger,
        DatabaseLogger $logger
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->fileLogger = $fileLogger;
        $this->logger = $logger;
        $this->purchaseDataService = $purchaseDataService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->transferPriceService = $transferPriceService;
        $this->invoiceService = $invoiceService;
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
        $purchase->setPayment([Purchase::PAYMENT_PAYMENT_ID => $paymentTransfer->getId()]);

        try {
            $invoiceId = $this->issueInvoice($purchase, $finalizedPayment, $serviceModule->service);
            $purchase->setPayment([Purchase::PAYMENT_INVOICE_ID => $invoiceId]);
        } catch (InvoiceException $e) {
            \Sentry\captureException($e);
            $this->fileLogger->error(
                "{$e->getMessage()} Payment ID: {$finalizedPayment->getOrderId()}"
            );
            $this->logger->logWithUser(
                $purchase->user,
                "log_invoice_issue_failure",
                $finalizedPayment->getOrderId(),
                $e->getMessage()
            );
        }

        $boughtServiceId = $serviceModule->purchase($purchase);
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

    private function issueInvoice(
        Purchase $purchase,
        FinalizedPayment $finalizedPayment,
        Service $service
    ): ?string {
        if (!$this->invoiceService->isConfigured()) {
            return null;
        }

        if ($finalizedPayment->isTestMode()) {
            $this->fileLogger->info(
                "Invoice wasn't issued due to test mode. Payment ID: {$finalizedPayment->getOrderId()}"
            );
            return null;
        }

        if ($purchase->getBillingAddress()->isEmpty()) {
            throw new IssueInvoiceException("Invoice wasn't issued due to empty billing address.");
        }

        $email = $purchase->getEmail() ?? $purchase->user->getEmail();
        if (!$email) {
            throw new IssueInvoiceException("Invoice won't be sent due to lack of email.");
        }

        try {
            $invoiceId = $this->invoiceService->create(
                $purchase->getBillingAddress(),
                new PurchaseItem(
                    $purchase->getServiceId(),
                    $purchase->getServiceName(),
                    $finalizedPayment->getCost(),
                    $service->getTaxRate(),
                    $service->getFlatRateTax(),
                    $service->getPKWiUSymbol()
                ),
                $email
            );
        } catch (InvoiceServiceUnavailableException $e) {
            // The infakt client is not configured
            return null;
        }

        $this->logger->logWithUser(
            $purchase->user,
            "log_invoice_issue_success",
            $finalizedPayment->getOrderId(),
            $invoiceId
        );

        return $invoiceId;
    }
}
