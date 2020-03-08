<?php
namespace App\Payment\DirectBilling;

use App\Loggers\DatabaseLogger;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\General\ExternalPaymentService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\System\Heart;

class DirectBillingService
{
    /** @var ExternalPaymentService */
    private $externalPaymentService;

    /** @var DatabaseLogger */
    private $logger;

    /** @var Heart */
    private $heart;

    public function __construct(
        ExternalPaymentService $externalPaymentService,
        Heart $heart,
        DatabaseLogger $logger
    ) {
        $this->externalPaymentService = $externalPaymentService;
        $this->logger = $logger;
        $this->heart = $heart;
    }

    public function finalizePurchase(FinalizedPayment $finalizedPayment)
    {
        $purchase = $this->externalPaymentService->restorePurchase(
            $finalizedPayment->getDataFilename()
        );

        if (!$purchase) {
            $this->logger->log('transfer_no_data_file', $finalizedPayment->getOrderId());
            return false;
        }

        if (
            $finalizedPayment->getAmount() !==
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        ) {
            // TODO Log invalid amount
            $finalizedPayment->setStatus(false);
        }

        if (!$finalizedPayment->getStatus()) {
            $this->logger->log(
                'payment_not_accepted',
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getAmount() / 100,
                $finalizedPayment->getExternalServiceId()
            );
            return false;
        }

        // TODO Avoid multiple
        // TODO Store info about direct billing payment

        $this->externalPaymentService->deletePurchase($finalizedPayment->getDataFilename());

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'external_no_purchase',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $finalizedPayment->getOrderId(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            'external_payment_accepted',
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getAmount() / 100,
            $finalizedPayment->getExternalServiceId()
        );

        return true;
    }
}
