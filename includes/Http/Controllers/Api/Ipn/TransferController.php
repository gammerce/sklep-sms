<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Managers\PaymentModuleManager;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Payment\Transfer\TransferPaymentService;
use App\Payment\Transfer\TransferPriceService;
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
        TransferPriceService $transferPriceService,
        DatabaseLogger $logger
    ) {
        $paymentModule = $paymentModuleManager->getByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse(
                "Payment platform does not support transfer payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeTransfer($request);

        try {
            $purchase = $externalPaymentService->restorePurchase($finalizedPayment);
        } catch (LackOfValidPurchaseDataException $e) {
            $logger->log(
                "log_external_payment_no_transaction_file",
                $finalizedPayment->getOrderId()
            );
            return new PlainResponse($finalizedPayment->getOutput());
        }

        try {
            $transferPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $logger->log(
                "log_external_payment_invalid_amount",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $transferPriceService->getPrice($purchase)
            );
        } catch (PaymentRejectedException $e) {
            $logger->log(
                "log_external_payment_not_accepted",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost() / 100,
                $finalizedPayment->getExternalServiceId()
            );
        } catch (InvalidServiceModuleException $e) {
            $logger->log(
                "log_external_payment_invalid_module",
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );
        } finally {
            return new PlainResponse($finalizedPayment->getOutput());
        }
    }
}
