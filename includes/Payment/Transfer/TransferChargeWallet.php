<?php
namespace App\Payment\Transfer;

use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class TransferChargeWallet implements IChargeWallet
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                'transfer_price' => as_float(array_get($body, 'transfer_price')),
            ],
            [
                'transfer_price' => [new RequiredRule(), new NumberRule(), new MinValueRule(1.01)],
            ]
        );
        $validated = $validator->validateOrFail();
        $transferPrice = $validated["transfer_price"];

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_TRANSFER => intval($transferPrice * 100),
            Purchase::PAYMENT_DISABLED_TRANSFER => false,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => intval($transferPrice * 100),
        ]);
    }

    public function getTransactionView(Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText($transaction->getQuantity() * 100);
        return $this->template->renderNoComments(
            "services/charge_wallet/web_purchase_info_transfer",
            compact('quantity')
        );
    }

    public function getOptionView()
    {
        if (!$this->settings->getTransferPlatformId()) {
            return null;
        }

        $option = $this->template->render("services/charge_wallet/option", [
            'value' => Purchase::METHOD_TRANSFER,
            'text' => $this->lang->t('transfer_transfer'),
        ]);
        $body = $this->template->render("services/charge_wallet/transfer_body", [
            'type' => Purchase::METHOD_TRANSFER,
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
    }
}
