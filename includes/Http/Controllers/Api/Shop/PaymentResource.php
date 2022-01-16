<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Validation\Rules\EnumRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\BillingAddress;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Payment\General\PurchaseDataService;
use App\Payment\Invoice\InvoiceService;
use App\Repositories\UserRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PaymentResource
{
    public function post(
        $transactionId,
        Request $request,
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager,
        UserRepository $userRepository
    ) {
        $lang = $translationManager->user();

        $purchase = $purchaseDataService->restorePurchase($transactionId);
        if (!$purchase) {
            throw new EntityNotFoundException();
        }

        $billingRequiredRule = $invoiceService->isConfigured() ? [new RequiredRule()] : [];
        $validator = new Validator($request->request->all(), [
            "method" => [new EnumRule(PaymentMethod::class)],
            "payment_platform_id" => [],
            "sms_code" => [],
            "billing_address_name" => [...$billingRequiredRule, new MaxLengthRule(128)],
            "billing_address_vat_id" => [new MaxLengthRule(128)],
            "billing_address_street" => [...$billingRequiredRule, new MaxLengthRule(128)],
            "billing_address_postal_code" => [...$billingRequiredRule, new MaxLengthRule(128)],
            "billing_address_city" => [...$billingRequiredRule, new MaxLengthRule(128)],
            "remember_billing_address" => [],
        ]);
        $validated = $validator->validateOrFail();

        $paymentPlatformId = as_int($validated["payment_platform_id"]);
        $paymentMethod = new PaymentMethod($validated["method"]);
        $smsCode = trim($validated["sms_code"]);
        $billingAddress = new BillingAddress(
            trim($validated["billing_address_name"]),
            trim($validated["billing_address_vat_id"]),
            trim($validated["billing_address_street"]),
            trim($validated["billing_address_postal_code"]),
            trim($validated["billing_address_city"])
        );

        $paymentOption = new PaymentOption($paymentMethod, $paymentPlatformId);

        if (!$purchase->getPaymentSelect()->contains($paymentOption)) {
            return new ErrorApiResponse("Invalid payment option");
        }

        if (!$billingAddress->isEmpty() && $validated["remember_billing_address"]) {
            $purchase->user->setBillingAddress($billingAddress);
            $userRepository->update($purchase->user);
        }

        $purchase
            ->setBillingAddress($billingAddress)
            ->setPaymentOption($paymentOption)
            ->setPayment([
                Purchase::PAYMENT_SMS_CODE => $smsCode,
            ]);

        try {
            $paymentResult = $paymentService->makePayment($purchase);
        } catch (PaymentProcessingException $e) {
            return new ApiResponse($e->getCode(), $e->getMessage(), false);
        }

        switch ($paymentResult->getType()) {
            case PaymentResultType::PURCHASED():
                $purchaseDataService->deletePurchase($purchase);

                return new ApiResponse("purchased", $lang->t("purchase_success"), true, [
                    "bsid" => $paymentResult->getData(),
                ]);

            case PaymentResultType::EXTERNAL():
                // Let's store changes made to purchase object
                // since it will be used later
                $purchaseDataService->storePurchase($purchase);

                return new ApiResponse("external", $lang->t("external_payment_prepared"), true, [
                    "data" => $paymentResult->getData(),
                ]);

            default:
                throw new UnexpectedValueException("Unexpected result type");
        }
    }
}
