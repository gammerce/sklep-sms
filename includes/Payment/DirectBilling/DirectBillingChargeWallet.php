<?php
namespace App\Payment\DirectBilling;

use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Managers\PaymentModuleManager;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
use App\Payment\Interfaces\IChargeWallet;
use App\Services\PriceTextService;
use App\Support\Template;
use App\System\Settings;
use App\Verification\Abstracts\SupportDirectBilling;
use UnexpectedValueException;

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

        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM)
        );

        if (!($paymentModule instanceof SupportDirectBilling)) {
            throw new UnexpectedValueException("Payment module doesn't support direct billing");
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => $price,
            Purchase::PAYMENT_DISABLED_DIRECT_BILLING => false,
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

    public function getOptionView()
    {
        if (!$this->settings->getDirectBillingPlatformId()) {
            return null;
        }

        $option = $this->template->render("shop/services/charge_wallet/option", [
            "value" => PaymentMethod::DIRECT_BILLING(),
            "text" => "Direct Billing",
        ]);
        $body = $this->template->render("shop/services/charge_wallet/direct_billing_body", [
            "type" => PaymentMethod::DIRECT_BILLING(),
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
