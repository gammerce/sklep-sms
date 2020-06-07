<?php
namespace App\Payment\Transfer;

use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
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

    /** @var TransferPriceService */
    private $transferPriceService;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        TransferPriceService $transferPriceService,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->transferPriceService = $transferPriceService;
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                "transfer_price" => array_get($body, "transfer_price"),
            ],
            [
                "transfer_price" => [new RequiredRule(), new NumberRule(), new MinValueRule(1.01)],
            ]
        );
        $validated = $validator->validateOrFail();
        $transferPrice = price_to_int($validated["transfer_price"]);

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_TRANSFER => $transferPrice,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $transferPrice,
        ]);
        $purchase->getPaymentSelect()->allowPaymentMethod(PaymentMethod::TRANSFER());
    }

    public function getTransactionView(Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText(
            price_to_int($transaction->getQuantity())
        );
        return $this->template->renderNoComments(
            "shop/services/charge_wallet/web_purchase_info_transfer",
            compact("quantity")
        );
    }

    public function getOptionView()
    {
        if (!$this->settings->getTransferPlatformId()) {
            return null;
        }

        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => PaymentMethod::TRANSFER(),
            "text" => $this->lang->t("transfer_transfer"),
        ]);
        $body = $this->template->render("shop/services/charge_wallet/transfer_body", [
            "type" => PaymentMethod::TRANSFER(),
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase)
    {
        return $this->priceTextService->getPriceText(
            $this->transferPriceService->getPrice($purchase)
        );
    }

    public function getQuantity(Purchase $purchase)
    {
        return $this->priceTextService->getPriceText($purchase->getOrder(Purchase::ORDER_QUANTITY));
    }
}
