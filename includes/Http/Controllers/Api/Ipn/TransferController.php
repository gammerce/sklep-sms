<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Payment\Transfer\TransferPaymentService;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;
use Symfony\Component\HttpFoundation\Request;

class TransferController
{
    public function action(
        $paymentPlatform,
        Request $request,
        Heart $heart,
        ExternalPaymentService $externalPaymentService,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $heart->getPaymentModuleByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse(
                "Payment platform does not support transfer payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeTransfer(
            $request->query->all(),
            $request->request->all()
        );

        try {
            $purchase = $externalPaymentService->restorePurchase($finalizedPayment);
        } catch (LackOfValidPurchaseDataException $e) {
            $logger->log('log_purchase_no_data_file', $finalizedPayment->getOrderId());
            return new PlainResponse($finalizedPayment->getOutput());
        }

        try {
            $transferPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $logger->log(
                'log_payment_invalid_amount',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
            );
        } catch (PaymentRejectedException $e) {
            $logger->log(
                'log_external_payment_not_accepted',
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost() / 100,
                $finalizedPayment->getExternalServiceId()
            );
        } catch (InvalidServiceModuleException $e) {
            $logger->log(
                'log_external_no_purchase',
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );
        } finally {
            return new PlainResponse($finalizedPayment->getOutput());
        }
    }

    /**
     * @deprecated
     */
    public function oldAction(
        Request $request,
        Heart $heart,
        ExternalPaymentService $externalPaymentService,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $databaseLogger
    ) {
        return $this->action(
            $request->query->get('service'),
            $request,
            $heart,
            $externalPaymentService,
            $transferPaymentService,
            $databaseLogger
        );
    }
}
