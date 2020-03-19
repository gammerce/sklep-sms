<?php
namespace App\Payment\Transfer;

use App\Loggers\DatabaseLogger;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\General\ExternalPaymentService;
use App\Repositories\PaymentTransferRepository;

class TransferPaymentService
{
    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ExternalPaymentService $externalPaymentService,
        DatabaseLogger $logger
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->externalPaymentService = $externalPaymentService;
        $this->logger = $logger;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function finalizePurchase(FinalizedPayment $finalizedPayment)
    {
        $purchase = $this->externalPaymentService->restorePurchase($finalizedPayment);

        if (!$purchase) {
            return false;
        }

        if (
            $finalizedPayment->getCost() !==
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        ) {
            $this->logger->log(
                'log_payment_invalid_amount',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
            );
            $finalizedPayment->setStatus(false);
        }

        if (!$this->externalPaymentService->validate($finalizedPayment)) {
            return false;
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

        return $this->externalPaymentService->finalizePurchase($purchase, $finalizedPayment);
    }
}
