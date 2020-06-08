<?php
namespace App\Payment\Transfer;

use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class TransferChargeWallet implements IChargeWallet
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Translator */
    private $lang;

    /** @var TransferPriceService */
    private $transferPriceService;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        TransferPriceService $transferPriceService,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
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

    public function getOptionView(PaymentPlatform $paymentPlatform)
    {
        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => get_charge_wallet_option(PaymentMethod::TRANSFER(), $paymentPlatform),
            "text" => $this->lang->t("payment_option_transfer", $paymentPlatform->getName()),
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
