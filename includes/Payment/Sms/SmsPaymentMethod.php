<?php
namespace App\Payment\Sms;

use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\CustomErrorException;
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

    public function getPaymentDetails(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        $smsPaymentModule = $this->paymentModuleManager->get($paymentPlatform);

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

    public function isAvailable(Purchase $purchase, PaymentPlatform $paymentPlatform = null)
    {
        $smsPaymentModule = $this->paymentModuleManager->get($paymentPlatform);
        $price = $this->smsPriceService->getPrice($purchase);

        return $smsPaymentModule instanceof SupportSms &&
            $price !== null &&
            $this->smsPriceService->isPriceAvailable($price, $smsPaymentModule);
    }

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return PaymentResult
     * @throws PaymentProcessingException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPaymentOption()->getPaymentPlatformId()
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

        try {
            // Let's check sms code
            $paymentId = $this->smsPaymentService->payWithSms(
                $paymentModule,
                $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
                $price,
                $purchase->user,
                $purchase->getAddressIp(),
                $purchase->getPlatform()
            );
        } catch (CustomErrorException $e) {
            throw new PaymentProcessingException(
                $e->getErrorCode(),
                $this->lang->t("sms_info_custom_error", $e->getMessage())
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
