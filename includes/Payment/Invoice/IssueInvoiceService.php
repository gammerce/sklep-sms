<?php

namespace App\Payment\Invoice;

use App\Loggers\DatabaseLogger;
use App\Loggers\FileLogger;
use App\Managers\ServiceManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\General\ExternalPaymentService;
use App\Payment\General\PurchaseDataService;
use App\Payment\Transfer\IssueInvoiceException;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\TransactionRepository;
use App\Support\Money;
use function PHPUnit\Framework\assertSame;

class IssueInvoiceService
{
    private BoughtServiceRepository $boughtServiceRepository;
    private InvoiceService $invoiceService;
    private DatabaseLogger $logger;
    private ServiceManager $serviceManager;
    private PurchaseDataService $purchaseDataService;
    private TransactionRepository $transactionRepository;

    public function __construct(
        BoughtServiceRepository $boughtServiceRepository,
        InvoiceService $invoiceService,
        DatabaseLogger $logger,
        PurchaseDataService $purchaseDataService,
        ServiceManager $serviceManager,
        TransactionRepository $transactionRepository
    ) {
        $this->boughtServiceRepository = $boughtServiceRepository;
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
        $this->serviceManager = $serviceManager;
        $this->purchaseDataService = $purchaseDataService;
        $this->transactionRepository = $transactionRepository;
    }

    public function issue(
        Purchase $purchase,
        FinalizedPayment $finalizedPayment,
        Service $service
    ): ?string {
        if (!$this->invoiceService->isConfigured()) {
            return null;
        }

        if ($finalizedPayment->isTestMode()) {
            $this->logger->log(
                "Invoice wasn't issued due to test mode. Payment ID: {$finalizedPayment->getOrderId()}"
            );
            return null;
        }

        if ($finalizedPayment->getCost()->asInt() === 0) {
            $this->logger->log(
                "Invoice wasn't issued due to no cost involved. Payment ID: {$finalizedPayment->getOrderId()}"
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

    /**
     * @param string $transactionId Purchase ID
     * @param string $paymentId Payment ID
     * @return string
     * @throws IssueInvoiceException
     */
    public function reissue(string $transactionId, string $paymentId): string
    {
        $purchase = $this->purchaseDataService->restorePurchaseForcefully($transactionId);
        if (!$purchase) {
            throw new IssueInvoiceException("There is no purchase with such ID");
        }

        $service = $this->serviceManager->get($purchase->getServiceId());
        $transaction = $this->transactionRepository->getByPaymentId($paymentId);

        if ($transaction->getInvoiceId()) {
            throw new IssueInvoiceException("Invoice was already invoiced");
        }

        $finalizedPayment = (new FinalizedPayment())
            ->setTransactionId($transactionId)
            ->setOrderId($transaction->getPaymentId())
            ->setCost($transaction->getCost());

        $invoiceId = $this->issue($purchase, $finalizedPayment, $service);
        $this->boughtServiceRepository->update($transaction->getId(), $invoiceId);

        return $invoiceId;
    }
}
