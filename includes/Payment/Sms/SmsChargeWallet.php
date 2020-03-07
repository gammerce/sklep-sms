<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SmsPriceExistsRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Models\Transaction;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Support\Template;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;

class SmsChargeWallet implements IChargeWallet
{
    /** @var Heart */
    private $heart;

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

    public function __construct(
        Heart $heart,
        SmsPriceService $smsPriceService,
        PriceTextService $priceTextService,
        Template $template,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->smsPriceService = $smsPriceService;
        $this->priceTextService = $priceTextService;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->settings = $settings;
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                'sms_price' => as_int(array_get($body, 'sms_price')),
            ],
            [
                'sms_price' => [new RequiredRule(), new SmsPriceExistsRule()],
            ]
        );
        $validated = $validator->validateOrFail();
        $smsPrice = $validated['sms_price'];

        $smsPaymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_SMS)
        );

        if (!($smsPaymentModule instanceof SupportSms)) {
            return;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_SMS => $smsPrice,
            Purchase::PAYMENT_DISABLED_SMS => false,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $this->smsPriceService->getProvision(
                $smsPrice,
                $smsPaymentModule
            ),
        ]);
    }

    public function getTransactionView(Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText($transaction->getQuantity() * 100);
        $desc = $this->lang->t('wallet_was_charged', $quantity);

        return $this->template->renderNoComments("services/charge_wallet/web_purchase_info_sms", [
            'desc' => $desc,
            'smsNumber' => $transaction->getSmsNumber(),
            'smsText' => $transaction->getSmsText(),
            'smsCode' => $transaction->getSmsCode(),
            'cost' => $this->priceTextService->getPriceText($transaction->getCost() ?: 0),
        ]);
    }

    public function getOptionView()
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $this->settings->getSmsPlatformId()
        );

        if (!($paymentModule instanceof SupportSms)) {
            return null;
        }

        $option = $this->template->render("services/charge_wallet/option", [
            'value' => Purchase::METHOD_SMS,
            'text' => "SMS",
        ]);

        $smsList = collect($paymentModule::getSmsNumbers())
            ->map(function (SmsNumber $smsNumber) {
                $provision = number_format($smsNumber->getProvision() / 100.0, 2);
                return create_dom_element(
                    "option",
                    $this->lang->t(
                        'charge_sms_option',
                        $this->priceTextService->getPriceGrossText($smsNumber->getPrice()),
                        $this->settings->getCurrency(),
                        $provision,
                        $this->settings->getCurrency()
                    ),
                    [
                        'value' => $smsNumber->getPrice(),
                    ]
                );
            })
            ->join();

        $body = $this->template->render("services/charge_wallet/sms_body", [
            "smsList" => $smsList,
            "type" => Purchase::METHOD_SMS,
        ]);

        return [$option, $body];
    }
}
