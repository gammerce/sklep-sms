<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Payment\Transfer\TransferPaymentService;
use App\Verification\Abstracts\SupportTransfer;
use Symfony\Component\HttpFoundation\Request;

class TransferController
{
    public function action(
        $paymentPlatform,
        Request $request,
        PaymentModuleManager $paymentModuleManager,
        ExternalPaymentService $externalPaymentService,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $paymentModuleManager->getByPlatformId($paymentPlatform);

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
            $logger->log(
                'log_external_payment_no_transaction_file',
                $finalizedPayment->getOrderId()
            );
            return new PlainResponse($finalizedPayment->getOutput());
        }

        try {
            $transferPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $logger->log(
                'log_external_payment_invalid_amount',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
            );
        } catch (PaymentRejectedException $e) {
            $logger->log(
                'log_external_payment_not_accepted',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost() / 100,
                $finalizedPayment->getExternalServiceId()
            );
        } catch (InvalidServiceModuleException $e) {
            $logger->log(
                'log_external_payment_invalid_module',
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
        PaymentModuleManager $paymentModuleManager,
        ExternalPaymentService $externalPaymentService,
        TransferPaymentService $transferPaymentService,
        DatabaseLogger $databaseLogger
    ) {
        return $this->action(
            $request->query->get('service'),
            $request,
            $paymentModuleManager,
            $externalPaymentService,
            $transferPaymentService,
            $databaseLogger
        );
    }
}
