<?php
namespace App\Payment\General;

use App\Loggers\DatabaseLogger;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\System\Heart;

class ExternalPaymentService
{
    /** @var Heart */
    private $heart;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(
        DatabaseLogger $logger,
        Heart $heart,
        PurchaseDataService $purchaseDataService
    ) {
        $this->heart = $heart;
        $this->purchaseDataService = $purchaseDataService;
        $this->logger = $logger;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function validate(FinalizedPayment $finalizedPayment)
    {
        if (!$finalizedPayment->getStatus()) {
            $this->logger->log(
                'log_external_payment_not_accepted',
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost() / 100,
                $finalizedPayment->getExternalServiceId()
            );
        }

        return $finalizedPayment->getStatus();
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return Purchase|null
     */
    public function restorePurchase(FinalizedPayment $finalizedPayment)
    {
        $fileName = $finalizedPayment->getDataFilename();
        $purchase = $this->purchaseDataService->restorePurchase($fileName);

        if (!$purchase || $purchase->isAttempted()) {
            $this->logger->log('log_purchase_no_data_file', $finalizedPayment->getOrderId());
            return null;
        }

        $purchase->markAsAttempted();
        $this->purchaseDataService->updatePurchase($fileName, $purchase);
        return $purchase;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment)
    {
        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'log_external_no_purchase',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

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

        return true;
    }
}
