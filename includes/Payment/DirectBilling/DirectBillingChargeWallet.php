<?php
namespace App\Payment\DirectBilling;

use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
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

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var DirectBillingPriceService */
    private $directBillingPriceService;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        Settings $settings,
        PaymentModuleManager $paymentModuleManager,
        DirectBillingPriceService $directBillingPriceService
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->settings = $settings;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->directBillingPriceService = $directBillingPriceService;
    }

    public function setup(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                "direct_billing_price" => price_to_int(array_get($body, "direct_billing_price")),
            ],
            [
                "direct_billing_price" => [new RequiredRule(), new NumberRule()],
            ]
        );
        $validated = $validator->validateOrFail();
        $price = $validated["direct_billing_price"];

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => $price,
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
        $paymentOptionId = make_charge_wallet_option(PaymentMethod::DIRECT_BILLING(), $paymentPlatform);
        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => $paymentOptionId,
            "text" => "Direct Billing",
        ]);
        $body = $this->template->render("shop/services/charge_wallet/direct_billing_body", [
            "option" => $paymentOptionId,
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase)
    {
        return $this->priceTextService->getPriceText(
            $this->directBillingPriceService->getPrice($purchase)
        );
    }

    public function getQuantity(Purchase $purchase)
    {
        $price = $this->directBillingPriceService->getPrice($purchase) / $this->settings->getVat();
        $minQuantity = $this->priceTextService->getPriceText($price * 0.5);
        $maxQuantity = $this->priceTextService->getPriceText($price * 0.7);
        return "W zależności od operatora i ceny, od $minQuantity do $maxQuantity";
    }
}
