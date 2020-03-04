<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SmsPriceExistsRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Services\SmsPriceService;
use App\Support\Template;
use App\System\Heart;
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

    public function __construct(
        Heart $heart,
        SmsPriceService $smsPriceService,
        PriceTextService $priceTextService,
        Template $template,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->smsPriceService = $smsPriceService;
        $this->priceTextService = $priceTextService;
        $this->template = $template;
        $this->lang = $translationManager->user();
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
}
