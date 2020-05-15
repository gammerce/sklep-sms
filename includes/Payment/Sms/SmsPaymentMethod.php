<?php
namespace App\Payment\Sms;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\SmsPaymentException;

class SmsPaymentMethod implements IPaymentMethod
{
    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var SmsPaymentService */
    private $smsPaymentService;

    /** @var Translator */
    private $lang;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        SmsPriceService $smsPriceService,
        SmsPaymentService $smsPaymentService,
        TranslationManager $translationManager,
        PaymentModuleManager $paymentModuleManager
    ) {
        $this->smsPriceService = $smsPriceService;
        $this->smsPaymentService = $smsPaymentService;
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return null;
        }

        $smsNumber = $this->smsPriceService->getNumber(
            $this->smsPriceService->getPrice($purchase),
            $smsPaymentModule
        );

        return array_merge($this->smsPriceService->getOldAndNewPrice($purchase), [
            "sms_code" => $smsPaymentModule->getSmsCode(),
            "sms_number" => $smsNumber ? $smsNumber->getNumber() : null,
        ]);
    }

    public function isAvailable(Purchase $purchase)
    {
        if (
            !$purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS) ||
            $this->smsPriceService->getPrice($purchase) === null ||
            $purchase->getPayment(Purchase::PAYMENT_DISABLED_SMS)
        ) {
            return false;
        }

        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        return $smsPaymentModule instanceof SupportSms &&
            $this->smsPriceService->isPriceAvailable(
                $this->smsPriceService->getPrice($purchase),
                $smsPaymentModule
            );
    }

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return PaymentResult
     * @throws PaymentProcessingException
     * @throws ValidationException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($paymentModule instanceof SupportSms)) {
            throw new PaymentProcessingException(
                "sms_unavailable",
                $this->lang->t("sms_unavailable")
            );
        }

        $price = $this->smsPriceService->getPrice($purchase);

        if ($price === null) {
            throw new PaymentProcessingException(
                "no_sms_price",
                $this->lang->t("payment_method_unavailable")
            );
        }

        if ($price === 0) {
            // TODO Omit sms code verification
            dd("Not implemented");
        }

        $validator = new Validator(
            [
                "sms_code" => $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
            ],
            [
                "sms_code" => [new RequiredRule(), new MaxLengthRule(16)],
            ]
        );
        $validator->validateOrFail();

        try {
            // Let's check sms code
            $paymentId = $this->smsPaymentService->payWithSms(
                $paymentModule,
                $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
                $this->smsPriceService->getNumber($price, $paymentModule),
                $purchase->user
            );
        } catch (SmsPaymentException $e) {
            throw new PaymentProcessingException(
                $e->getErrorCode(),
                $this->getSmsExceptionMessage($e)
            );
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return new PaymentResult(PaymentResultType::PURCHASED(), $boughtServiceId);
    }

    private function getSmsExceptionMessage(SmsPaymentException $e)
    {
        return $e->getMessage() ?:
            $this->lang->t("sms_info_" . $e->getErrorCode()) ?:
            $e->getErrorCode();
    }
}
