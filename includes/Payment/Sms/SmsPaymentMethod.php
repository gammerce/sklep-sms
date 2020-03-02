<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;

class SmsPaymentMethod implements IPaymentMethod
{
    /** @var Heart */
    private $heart;

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

    public function __construct(
        Heart $heart,
        SmsPriceService $smsPriceService,
        Template $template,
        PriceTextService $priceTextService,
        SmsPaymentService $smsPaymentService,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->smsPriceService = $smsPriceService;
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->smsPaymentService = $smsPaymentService;
        $this->lang = $translationManager->user();
    }

    public function render(Purchase $purchase)
    {
        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return null;
        }

        $smsNumber = $this->smsPriceService->getNumber(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
            $smsPaymentModule
        );
        $paymentMethods[] = $this->template->render('payment_method_sms', [
            'priceGross' => $this->priceTextService->getPriceGrossText(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS)
            ),
            'smsCode' => $smsPaymentModule->getSmsCode(),
            'smsNumber' => $smsNumber ? $smsNumber->getNumber() : null,
        ]);
    }

    public function isAvailable(Purchase $purchase)
    {
        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS) !== null &&
            $smsPaymentModule instanceof SupportSms &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_SMS) &&
            $this->smsPriceService->isPriceAvailable(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
                $smsPaymentModule
            );
    }

    public function pay(Purchase $purchase, ServiceModule $serviceModule)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($paymentModule instanceof SupportSms)) {
            return [
                'status' => "sms_unavailable",
                'text' => $this->lang->t('sms_unavailable'),
                'positive' => false,
            ];
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_SMS) === null) {
            return [
                'status' => "no_sms_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
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

        // Let's check sms code
        $result = $this->smsPaymentService->payWithSms(
            $paymentModule,
            $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
            $this->smsPriceService->getNumber(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_SMS),
                $paymentModule
            ),
            $purchase->user
        );

        if ($result['status'] !== 'ok') {
            return [
                'status' => $result['status'],
                'text' => $result['text'],
                'positive' => false,
            ];
        }

        $paymentId = $result['payment_id'];

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return [
            'status' => "purchased",
            'text' => $this->lang->t('purchase_success'),
            'positive' => true,
            'data' => ['bsid' => $boughtServiceId],
        ];
    }
}
