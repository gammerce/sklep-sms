<?php
namespace App\Payment\Sms;

use App\Models\Purchase;
use App\Payment\Interfaces\IPurchaseRenderer;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Support\Template;
use App\System\Heart;
use App\Verification\Abstracts\SupportSms;

class PurchaseRenderer implements IPurchaseRenderer
{
    /** @var Heart */
    private $heart;

    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Heart $heart,
        SmsPriceService $smsPriceService,
        Template $template,
        PriceTextService $priceTextService
    ) {
        $this->heart = $heart;
        $this->smsPriceService = $smsPriceService;
        $this->template = $template;
        $this->priceTextService = $priceTextService;
    }

    public function render(Purchase $purchase)
    {
        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM)
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return null;
        }

        $smsNumber = $this->smsPriceService->getNumber(
            $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE),
            $smsPaymentModule
        );
        $paymentMethods[] = $this->template->render('payment_method_sms', [
            'priceGross' => $this->priceTextService->getPriceGrossText(
                $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE)
            ),
            'smsCode' => $smsPaymentModule->getSmsCode(),
            'smsNumber' => $smsNumber ? $smsNumber->getNumber() : null,
        ]);
    }

    public function isAvailable(Purchase $purchase)
    {
        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM)
        );

        return $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM) &&
            $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE) !== null &&
            $smsPaymentModule instanceof SupportSms &&
            !$purchase->getPayment(Purchase::PAYMENT_SMS_DISABLED) &&
            $this->smsPriceService->isPriceAvailable(
                $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE),
                $smsPaymentModule
            );
    }
}
