<?php
namespace App\Payment\DirectBilling;

use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;
use App\System\Settings;

class DirectBillingChargeWallet implements IChargeWallet
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Settings */
    private $settings;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        Settings $settings
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->settings = $settings;
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                'direct_billing_price' => as_float(array_get($body, 'direct_billing_price')),
            ],
            [
                'direct_billing_price' => [new RequiredRule(), new NumberRule()],
            ]
        );
        $validated = $validator->validateOrFail();
        $price = $validated["direct_billing_price"];

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => $price * 100,
            Purchase::PAYMENT_DISABLED_DIRECT_BILLING => false,
        ]);
        $purchase->setOrder([
            // TODO Calculate quantity
            Purchase::ORDER_QUANTITY => $price * 100,
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
        if (!$this->settings->getDirectBillingPlatformId()) {
            return null;
        }

        $option = $this->template->render("services/charge_wallet/option", [
            'value' => Purchase::METHOD_DIRECT_BILLING,
            'text' => "Direct Billing",
        ]);
        $body = $this->template->render("services/charge_wallet/direct_billing_body", [
            "type" => Purchase::METHOD_DIRECT_BILLING,
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING);
    }
}
