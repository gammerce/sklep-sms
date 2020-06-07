<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SmsPriceExistsRule;
use App\Http\Validation\Validator;
use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;

class SmsChargeWallet implements IChargeWallet
{
    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

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
        $smsPrice = $validated["sms_price"];

        $smsPaymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPaymentOption()->getPaymentPlatformId()
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_SMS => $smsPrice,
        ]);
        $purchase->getPaymentSelect()->allowPaymentMethod(PaymentMethod::SMS());
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $this->smsPriceService->getProvision(
                $smsPrice,
                $smsPaymentModule
            ),
        ]);
    }

    public function getTransactionView(Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText(
            price_to_int($transaction->getQuantity())
        );
        $desc = $this->lang->t("wallet_was_charged", $quantity);

        return $this->template->renderNoComments(
            "shop/services/charge_wallet/web_purchase_info_sms",
            [
                "desc" => $desc,
                "smsNumber" => $transaction->getSmsNumber(),
                "smsText" => $transaction->getSmsText(),
                "smsCode" => $transaction->getSmsCode(),
                "cost" => $this->priceTextService->getPriceText($transaction->getCost() ?: 0),
            ]
        );
    }

    public function getOptionView()
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $this->settings->getSmsPlatformId()
        );

        if (!($paymentModule instanceof SupportSms)) {
            return null;
        }

        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => PaymentMethod::SMS(),
            "text" => "SMS",
        ]);

        $smsList = collect($paymentModule::getSmsNumbers())
            ->map(function (SmsNumber $smsNumber) {
                return create_dom_element(
                    "option",
                    $this->lang->t(
                        "charge_sms_option",
                        $this->priceTextService->getPriceGrossText($smsNumber->getPrice()),
                        $this->priceTextService->getPriceText($smsNumber->getProvision())
                    ),
                    [
                        "value" => $smsNumber->getPrice(),
                    ]
                );
            })
            ->join();

        $body = $this->template->render("shop/services/charge_wallet/sms_body", [
            "smsList" => $smsList,
            "type" => PaymentMethod::SMS(),
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
