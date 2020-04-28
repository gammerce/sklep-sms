<?php
namespace App\Http\Controllers\Api\Ipn;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\PlainResponse;
use App\Loggers\DatabaseLogger;
use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Payment\DirectBilling\DirectBillingPaymentService;
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
        ExternalPaymentService $externalPaymentService,
        DirectBillingPaymentService $directBillingPaymentService
    ) {
        $paymentModule = $paymentModuleManager->getByPlatformId($paymentPlatform);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new PlainResponse(
                "Payment platform does not support direct billing payment [${paymentPlatform}]."
            );
        }

        $finalizedPayment = $paymentModule->finalizeDirectBilling(
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
            $directBillingPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $logger->log(
                'log_external_payment_invalid_amount',
                $purchase->getPayment(Purchase::PAYMENT_METHOD),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING)
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
}
