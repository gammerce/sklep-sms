<?php
namespace App\Payment\DirectBilling;

use App\Loggers\DatabaseLogger;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\General\ExternalPaymentService;
use App\Repositories\PaymentDirectBillingRepository;

class DirectBillingPaymentService
{
    /** @var DatabaseLogger */
    private $logger;

    /** @var PaymentDirectBillingRepository */
    private $paymentDirectBillingRepository;

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    public function __construct(
        DatabaseLogger $logger,
        PaymentDirectBillingRepository $paymentDirectBillingRepository,
        ExternalPaymentService $externalPaymentService
    ) {
        $this->logger = $logger;
        $this->paymentDirectBillingRepository = $paymentDirectBillingRepository;
        $this->externalPaymentService = $externalPaymentService;
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

        // Set charge amount to income value, since it was not set during the purchase process.
        // We don't know up front the income value.
        $purchase->setOrder([Purchase::ORDER_QUANTITY => $finalizedPayment->getIncome()]);

        if (
            $finalizedPayment->getCost() !==
            $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING)
        ) {
            $this->logger->log(
                'payment_invalid_amount',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING)
            );
            $finalizedPayment->setStatus(false);
        }

        if (!$this->externalPaymentService->validate($finalizedPayment)) {
            return false;
        }

        $paymentDirectBilling = $this->paymentDirectBillingRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome(),
            $finalizedPayment->getCost(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentDirectBilling->getId(),
        ]);

        return $this->externalPaymentService->finalizePurchase($purchase, $finalizedPayment);
    }
}
