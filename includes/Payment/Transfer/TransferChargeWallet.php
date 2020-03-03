<?php
namespace App\Payment\Sms;

use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;

class TransferChargeWallet implements IChargeWallet
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(Template $template, PriceTextService $priceTextService)
    {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
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
            Purchase::PAYMENT_PRICE_TRANSFER => $transferPrice * 100,
            Purchase::PAYMENT_DISABLED_TRANSFER => false,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $transferPrice * 100,
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
}
