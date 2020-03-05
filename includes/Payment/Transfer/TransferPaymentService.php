<?php
namespace App\Payment\Transfer;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Models\TransferFinalize;
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
     * @param TransferFinalize $transferFinalize
     * @return bool
     */
    public function transferFinalize(TransferFinalize $transferFinalize)
    {
        $paymentTransfer = $this->paymentTransferRepository->get($transferFinalize->getOrderId());

        // Avoid multiple authorization of the same order
        if ($paymentTransfer) {
            return false;
        }

        $purchase = $this->externalPaymentService->restorePurchase(
            $transferFinalize->getDataFilename()
        );

        if (!$purchase) {
            $this->logger->log('transfer_no_data_file', $transferFinalize->getOrderId());
            return false;
        }

        $this->paymentTransferRepository->create(
            $transferFinalize->getOrderId(),
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER),
            $transferFinalize->getTransferService(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $transferFinalize->isTestMode()
        );
        $this->externalPaymentService->deletePurchase($transferFinalize->getDataFilename());

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!$serviceModule) {
            $this->logger->log(
                'transfer_bad_module',
                $transferFinalize->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'transfer_no_purchase',
                $transferFinalize->getOrderId(),
                $purchase->getServiceId()
            );

            return false;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_TRANSFER,
            Purchase::PAYMENT_PAYMENT_ID => $transferFinalize->getOrderId(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->log(
            'payment_transfer_accepted',
            $boughtServiceId,
            $transferFinalize->getOrderId(),
            $transferFinalize->getAmount(),
            $transferFinalize->getTransferService(),
            $purchase->user->getUsername(),
            $purchase->user->getUid(),
            $purchase->user->getLastIp()
        );

        return true;
    }
}
