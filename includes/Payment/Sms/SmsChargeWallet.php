<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SmsPriceExistsRule;
use App\Http\Validation\Validator;
use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
use App\Payment\Interfaces\IChargeWallet;
use App\Support\Money;
use App\Support\PriceTextService;
use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\View\Html\Option;

class SmsChargeWallet implements IChargeWallet
{
    private SmsPriceService $smsPriceService;
    private PriceTextService $priceTextService;
    private Template $template;
    private Translator $lang;
    private Settings $settings;
    private PaymentModuleManager $paymentModuleManager;

    public function __construct(
        SmsPriceService $smsPriceService,
        PriceTextService $priceTextService,
        Template $template,
        PaymentModuleManager $paymentModuleManager,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->smsPriceService = $smsPriceService;
        $this->priceTextService = $priceTextService;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->settings = $settings;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                "sms_price" => as_int(array_get($body, "sms_price")),
            ],
            [
                "sms_price" => [new RequiredRule(), new SmsPriceExistsRule()],
            ]
        );
        $validated = $validator->validateOrFail();
        $smsPrice = new Money($validated["sms_price"]);

        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPaymentOption()->getPaymentPlatformId()
        );

        assert($smsPaymentModule instanceof SupportSms);

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_SMS => $smsPrice->asInt(),
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $this->smsPriceService
                ->getProvision($smsPrice, $smsPaymentModule)
                ->asInt(),
        ]);
    }

    public function getTransactionView(Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText(
            Money::fromPrice($transaction->getQuantity())
        );
        $desc = $this->lang->t("wallet_was_charged", $quantity);

        return $this->template->renderNoComments(
            "shop/services/charge_wallet/web_purchase_info_sms",
            [
                "desc" => $desc,
                "smsNumber" => $transaction->getSmsNumber(),
                "smsText" => $transaction->getSmsText(),
                "smsCode" => $transaction->getSmsCode(),
                "cost" => $this->priceTextService->getPriceText($transaction->getCost()),
            ]
        );
    }

    public function getOptionView(PaymentPlatform $paymentPlatform)
    {
        $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
        assert($paymentModule instanceof SupportSms);

        $smsList = collect($paymentModule->getSmsNumbers())
            ->map(
                fn(SmsNumber $smsNumber) => new Option(
                    $this->lang->t(
                        "charge_sms_option",
                        $this->priceTextService->getPriceGrossText($smsNumber->getPrice()),
                        $this->priceTextService->getPriceText($smsNumber->getProvision())
                    ),
                    $smsNumber->getPrice()->asInt()
                )
            )
            ->join();

        $paymentOptionId = make_charge_wallet_option(PaymentMethod::SMS(), $paymentPlatform);
        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => $paymentOptionId,
            "text" => "SMS",
        ]);
        $body = $this->template->render("shop/services/charge_wallet/sms_body", [
            "smsList" => $smsList,
            "option" => $paymentOptionId,
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase)
    {
        return $this->priceTextService->getPriceGrossText(
            $this->smsPriceService->getPrice($purchase)
        );
    }

    public function getQuantity(Purchase $purchase)
    {
        return $this->priceTextService->getPriceText($purchase->getOrder(Purchase::ORDER_QUANTITY));
    }
}
