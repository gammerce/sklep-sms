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
                'external_payment_not_accepted',
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost() / 100,
                $finalizedPayment->getExternalServiceId()
            );
            return false;
        }

        return true;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return Purchase|null
     */
    public function restorePurchase(FinalizedPayment $finalizedPayment)
    {
        $purchase = $this->purchaseDataService->restorePurchase(
            $finalizedPayment->getDataFilename()
        );

        if ($purchase) {
            return $purchase;
        }

        $this->logger->log('purchase_no_data_file', $finalizedPayment->getOrderId());
        return null;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment)
    {
        $this->purchaseDataService->deletePurchase($finalizedPayment->getDataFilename());

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'external_no_purchase',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            'external_payment_accepted',
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost() / 100,
            $finalizedPayment->getExternalServiceId()
        );

        return true;
    }
}
