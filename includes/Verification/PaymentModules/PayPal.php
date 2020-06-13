<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;

class PayPal extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "paypal";
    private $payPalDomain = "https://api.sandbox.paypal.com";

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    public function __construct(
        Requester $requester,
        PaymentPlatform $paymentPlatform,
        UrlGenerator $url,
        FileLogger $fileLogger,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        parent::__construct($requester, $paymentPlatform, $url, $fileLogger);
        $this->settings = $settings;
        $this->lang = $translationManager->user();
    }

    public static function getDataFields()
    {
        return [new DataField("client_id"), new DataField("secret")];
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        // TODO Listen to CHECKOUT.ORDER.APPROVED

        $price /= 100;
        $credentials = base64_encode("{$this->getClientId()}:{$this->getSecret()}");

        $response = $this->requester->post(
            "{$this->payPalDomain}/v2/checkout/orders",
            json_encode([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => $this->settings->getCurrency(),
                            "value" => $price,
                        ],
                        "description" => $purchase->getDescription(),
                        "custom_id" => $purchase->getId(),
                    ],
                ],
                "application_context" => [
                    "return_url" => $this->url->to("/page/payment_success"),
                    "cancel_url" => $this->url->to("/page/payment_error"),
                    "locale" => $this->lang->getCurrentLanguageShort(),
                    "shipping_preference" => "NO_SHIPPING",
                    "user_action" => "PAY_NOW",
                ],
            ]),
            [
                "Authorization" => "Basic $credentials",
                "Content-Type" => "application/json",
            ]
        );

        $result = $response->json();

        if (array_get($result, "status") !== "CREATED") {
            $this->fileLogger->error("Invalid order creation status", $result);
            throw new PaymentProcessingException("error", "Invalid order creation status");
        }

        foreach (array_get($result, "links", []) as $item) {
            if (array_get($item, "rel") === "approve") {
                return [
                    "method" => array_get($item, "method"),
                    "url" => array_get($item, "href"),
                ];
            }
        }

        $this->fileLogger->error("Approve url not found", $result);
        throw new PaymentProcessingException("error", "Approve url not found");
    }

    public function finalizeTransfer(array $query, array $body)
    {
        // TODO: Implement finalizeTransfer() method.
    }

    private function getClientId()
    {
        return $this->getData("client_id");
    }

    private function getSecret()
    {
        return $this->getData("secret");
    }
}
