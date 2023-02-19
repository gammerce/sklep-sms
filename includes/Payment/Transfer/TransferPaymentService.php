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
use App\Payment\Invoice\InvoiceException;
use App\Payment\Invoice\InvoiceService;
use App\Payment\Invoice\InvoiceServiceUnavailableException;
use App\Payment\Invoice\IssueInvoiceService;
use App\Payment\Invoice\PurchaseItem;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;

class TransferPaymentService
{
    private DatabaseLogger $logger;
    private FileLogger $fileLogger;
    private InvoiceService $invoiceService;
    private IssueInvoiceService $issueInvoiceService;
    private PaymentTransferRepository $paymentTransferRepository;
    private ServiceModuleManager $serviceModuleManager;
    private TransferPriceService $transferPriceService;

    public function __construct(
        DatabaseLogger $logger,
        FileLogger $fileLogger,
        InvoiceService $invoiceService,
        IssueInvoiceService $issueInvoiceService,
        PaymentTransferRepository $paymentTransferRepository,
        ServiceModuleManager $serviceModuleManager,
        TransferPriceService $transferPriceService
    ) {
        $this->fileLogger = $fileLogger;
        $this->invoiceService = $invoiceService;
        $this->issueInvoiceService = $issueInvoiceService;
        $this->logger = $logger;
        $this->paymentTransferRepository = $paymentTransferRepository;
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
            $invoiceId = $this->issueInvoiceService->issue(
                $purchase,
                $finalizedPayment,
                $serviceModule->service
            );
            $purchase->setPayment([Purchase::PAYMENT_INVOICE_ID => $invoiceId]);
        } catch (InvoiceException $e) {
            report_to_sentry($e);
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

        return $boughtServiceId;
    }
}
