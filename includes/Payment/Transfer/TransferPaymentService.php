<?php
namespace App\Payment\Transfer;

use App\Models\FinalizedPayment;
use App\Payment\General\ExternalPaymentService;
use App\Repositories\PaymentTransferRepository;

class TransferPaymentService
{
    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    public function __construct(
        PaymentTransferRepository $paymentTransferRepository,
        ExternalPaymentService $externalPaymentService
    ) {
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->externalPaymentService = $externalPaymentService;
    }

    /**
     * @param FinalizedPayment $finalizedPayment
     * @return bool
     */
    public function finalizePurchase(FinalizedPayment $finalizedPayment)
    {
        // Avoid multiple authorization of the same order
        if ($this->paymentTransferRepository->get($finalizedPayment->getOrderId())) {
            return false;
        }

        $purchase = $this->externalPaymentService->restorePurchase($finalizedPayment);

        if (!$purchase || !$this->externalPaymentService->validate($finalizedPayment)) {
            return false;
        }

        $this->paymentTransferRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome(),
            $finalizedPayment->getExternalServiceId(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        return $this->externalPaymentService->finalizePurchase($purchase, $finalizedPayment);
    }
}
