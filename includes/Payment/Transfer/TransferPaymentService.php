<?php
namespace App\Payment\Transfer;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Models\FinalizedPayment;
use App\Payment\General\ExternalPaymentService;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\System\Heart;

class TransferPaymentService
{
    /** @var Heart */
    private $heart;

    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var DatabaseLogger */
    private $logger;

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    public function __construct(
        Heart $heart,
        PaymentTransferRepository $paymentTransferRepository,
        ExternalPaymentService $externalPaymentService,
        DatabaseLogger $logger
    ) {
        $this->heart = $heart;
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->logger = $logger;
        $this->externalPaymentService = $externalPaymentService;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function finalizePurchase(FinalizedPayment $finalizedPayment)
    {
        $paymentTransfer = $this->paymentTransferRepository->get($finalizedPayment->getOrderId());

        // Avoid multiple authorization of the same order
        if ($paymentTransfer) {
            return false;
        }

        $purchase = $this->externalPaymentService->restorePurchase(
            $finalizedPayment->getDataFilename()
        );

        if (!$purchase) {
            $this->logger->log('transfer_no_data_file', $finalizedPayment->getOrderId());
            return false;
        }

        $this->paymentTransferRepository->create(
            $finalizedPayment->getOrderId(),
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER),
            $finalizedPayment->getExternalServiceId(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $finalizedPayment->isTestMode()
        );
        $this->externalPaymentService->deletePurchase($finalizedPayment->getDataFilename());

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!$serviceModule) {
            $this->logger->log(
                'transfer_bad_module',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'transfer_no_purchase',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_TRANSFER,
            Purchase::PAYMENT_PAYMENT_ID => $finalizedPayment->getOrderId(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->log(
            'payment_transfer_accepted',
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getAmount(),
            $finalizedPayment->getExternalServiceId(),
            $purchase->user->getUsername(),
            $purchase->user->getUid(),
            $purchase->user->getLastIp()
        );

        return true;
    }
}
