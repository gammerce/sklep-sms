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
use App\Support\Money;
use App\Support\PriceTextService;
use App\Support\Template;
use App\System\Settings;

class DirectBillingChargeWallet implements IChargeWallet
{
    private Template $template;
    private PriceTextService $priceTextService;
    private Settings $settings;
    private PaymentModuleManager $paymentModuleManager;
    private DirectBillingPriceService $directBillingPriceService;

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

    public function setup(Purchase $purchase, array $body): void
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

    public function getTransactionView(Transaction $transaction): string
    {
        $quantity = $this->priceTextService->getPriceText(
            Money::fromPrice($transaction->getQuantity())
        );
        return $this->template->renderNoComments(
            "shop/services/charge_wallet/web_purchase_info_transfer",
            compact("quantity")
        );
    }

    public function getOptionView(PaymentPlatform $paymentPlatform): array
    {
        $paymentOptionId = make_charge_wallet_option(
            PaymentMethod::DIRECT_BILLING(),
            $paymentPlatform
        );
        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => $paymentOptionId,
            "text" => "Direct Billing",
        ]);
        $body = $this->template->render("shop/services/charge_wallet/direct_billing_body", [
            "option" => $paymentOptionId,
        ]);

        return [$option, $body];
    }

    public function getPrice(Purchase $purchase): string
    {
        return $this->priceTextService->getPriceText(
            $this->directBillingPriceService->getPrice($purchase)
        );
    }

    public function getQuantity(Purchase $purchase): string
    {
        $price =
            $this->directBillingPriceService->getPrice($purchase)->asInt() /
            $this->settings->getVat();
        $minQuantity = $this->priceTextService->getPriceText($price * 0.5);
        $maxQuantity = $this->priceTextService->getPriceText($price * 0.7);
        return "W zależności od operatora i ceny, od $minQuantity do $maxQuantity";
    }
}
