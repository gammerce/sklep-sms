<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Managers\PaymentModuleManager;
use App\Payment\DirectBilling\DirectBillingPaymentService;
use App\Payment\DirectBilling\DirectBillingPriceService;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Verification\Abstracts\SupportDirectBilling;
use Symfony\Component\HttpFoundation\Request;

class DirectBillingController
{
    public function action(
        $paymentPlatform,
        Request $request,
        PaymentModuleManager $paymentModuleManager,
        DatabaseLogger $logger,
        DirectBillingPriceService $directBillingPriceService,
        ExternalPaymentService $externalPaymentService,
        DirectBillingPaymentService $directBillingPaymentService
    ) {
        $paymentModule = $paymentModuleManager->getByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new PlainResponse(
                "Payment platform does not support direct billing payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeDirectBilling($request);

        try {
            $purchase = $externalPaymentService->restorePurchase($finalizedPayment);
        } catch (LackOfValidPurchaseDataException $e) {
            $logger->log(
                "log_external_payment_no_transaction_file",
                $finalizedPayment->getTransactionId(),
                $finalizedPayment->getOrderId()
            );
            return new PlainResponse($finalizedPayment->getOutput());
        }

        try {
            $directBillingPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $logger->log(
                "log_external_payment_invalid_amount",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $directBillingPriceService->getPrice($purchase)
            );
        } catch (PaymentRejectedException $e) {
            $logger->log(
                "log_external_payment_not_accepted",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $finalizedPayment->getExternalServiceId()
            );
        } catch (InvalidServiceModuleException $e) {
            $logger->log(
                "log_external_payment_invalid_module",
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );
        }

        return new PlainResponse($finalizedPayment->getOutput());
    }
}
