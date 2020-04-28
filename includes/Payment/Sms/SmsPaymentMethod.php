<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Support\Result;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\SmsPaymentException;
use App\Managers\PaymentModuleManager;

class SmsPaymentMethod implements IPaymentMethod
{
    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var SmsPaymentService */
    private $smsPaymentService;

    /** @var Translator */
    private $lang;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        SmsPriceService $smsPriceService,
        Template $template,
        PriceTextService $priceTextService,
        SmsPaymentService $smsPaymentService,
        TranslationManager $translationManager,
        PaymentModuleManager $paymentModuleManager
    ) {
        $this->smsPriceService = $smsPriceService;
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->smsPaymentService = $smsPaymentService;
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function render(Purchase $purchase)
    {
        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return null;
        }

        $smsNumber = $this->smsPriceService->getNumber(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
            $smsPaymentModule
        );

        return $this->template->render("payment/payment_method_sms", [
            'priceGross' => $this->priceTextService->getPriceGrossText(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS)
            ),
            'smsCode' => $smsPaymentModule->getSmsCode(),
            'smsNumber' => $smsNumber ? $smsNumber->getNumber() : null,
        ]);
    }

    public function isAvailable(Purchase $purchase)
    {
        if (
            !$purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS) ||
            $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS) === null ||
            $purchase->getPayment(Purchase::PAYMENT_DISABLED_SMS)
        ) {
            return false;
        }

        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        return $smsPaymentModule instanceof SupportSms &&
            $this->smsPriceService->isPriceAvailable(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
                $smsPaymentModule
            );
    }

    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($paymentModule instanceof SupportSms)) {
            return new Result("sms_unavailable", $this->lang->t('sms_unavailable'), false);
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_SMS) === null) {
            return new Result("no_sms_price", $this->lang->t('payment_method_unavailable'), false);
        }

        $validator = new Validator(
            [
                'sms_code' => $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
            ],
            [
                'sms_code' => [new RequiredRule(), new MaxLengthRule(16)],
            ]
        );
        $validator->validateOrFail();

        try {
            // Let's check sms code
            $paymentId = $this->smsPaymentService->payWithSms(
                $paymentModule,
                $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
                $this->smsPriceService->getNumber(
                    $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
                    $paymentModule
                ),
                $purchase->user
            );
        } catch (SmsPaymentException $e) {
            return new Result($e->getErrorCode(), $this->getSmsExceptionMessage($e), false);
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return new Result("purchased", $this->lang->t('purchase_success'), true, [
            'bsid' => $boughtServiceId,
        ]);
    }

    private function getSmsExceptionMessage(SmsPaymentException $e)
    {
        return $e->getMessage() ?:
            $this->lang->t('sms_info_' . $e->getErrorCode()) ?:
            $e->getErrorCode();
    }
}
