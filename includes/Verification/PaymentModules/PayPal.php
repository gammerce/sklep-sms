<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Support\Money;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use Symfony\Component\HttpFoundation\Request;

class PayPal extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "paypal";

    private Settings $settings;
    private Translator $lang;

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

    /**
     * @return string
     */
    private function getPayPalDomain()
    {
        if ($this->isTestMode()) {
            return "https://api.sandbox.paypal.com";
        }

        return "https://api.paypal.com";
    }

    /**
     * @return bool
     */
    private function isTestMode()
    {
        return is_demo();
    }

    public static function getDataFields()
    {
        return [new DataField("client_id"), new DataField("secret")];
    }

    public function prepareTransfer(Money $price, Purchase $purchase)
    {
        $response = $this->requester->post(
            "{$this->getPayPalDomain()}/v2/checkout/orders",
            json_encode([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => $this->settings->getCurrency(),
                            "value" => $price->asPrice(),
                        ],
                        "description" => $purchase->getTransferDescription(),
                        "custom_id" => $purchase->getId(),
                    ],
                ],
                "application_context" => [
                    "return_url" => $this->url->to("/page/paypal_approved", [
                        "platform" => $this->paymentPlatform->getId(),
                    ]),
                    "cancel_url" => $this->url->to("/page/payment_error"),
                    "locale" => $this->lang->getCurrentLanguageShort(),
                    "shipping_preference" => "NO_SHIPPING",
                    "user_action" => "PAY_NOW",
                ],
            ]),
            [
                "Authorization" => "Basic {$this->getCredentials()}",
                "Content-Type" => "application/json",
            ]
        );

        $result = $response ? $response->json() : null;

        if (array_get($result, "status") !== "CREATED") {
            $this->fileLogger->error("Invalid order creation status", compact("result"));
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

        $this->fileLogger->error("Approve url not found", compact("result"));
        throw new PaymentProcessingException("error", "Approve url not found");
    }

    public function finalizeTransfer(Request $request)
    {
        $token = $request->query->get("token");
        $result = $this->capturePayment($token);

        $status = array_get($result, "status") === "COMPLETED";
        $purchaseUnits = array_dot_get($result, "purchase_units") ?: [[]];
        $purchaseUnit = $purchaseUnits[0];
        $captures = array_dot_get($purchaseUnit, "payments.captures") ?: [[]];
        $capture = $captures[0];
        $transactionId = array_dot_get($capture, "custom_id");
        $cost = Money::fromPrice(
            array_dot_get($capture, "seller_receivable_breakdown.gross_amount.value")
        );
        $income = Money::fromPrice(
            array_dot_get($capture, "seller_receivable_breakdown.net_amount.value")
        );

        if (!$status || !$transactionId) {
            $this->fileLogger->error("PayPal | Order capture failed", compact("result"));
        }

        return (new FinalizedPayment())
            ->setStatus($status)
            ->setOrderId($token)
            ->setCost($cost)
            ->setIncome($income)
            ->setTransactionId($transactionId)
            ->setTestMode($this->isTestMode());
    }

    /**
     * @param string $orderId
     * @return array
     */
    private function capturePayment($orderId)
    {
        $response = $this->requester->post(
            "{$this->getPayPalDomain()}/v2/checkout/orders/{$orderId}/capture",
            [],
            [
                "Authorization" => "Basic {$this->getCredentials()}",
                "Content-Type" => "application/json",
            ]
        );
        return $response ? $response->json() : null;
    }

    /**
     * @return string
     */
    private function getCredentials()
    {
        return base64_encode("{$this->getClientId()}:{$this->getSecret()}");
    }

    /**
     * @return string
     */
    private function getClientId()
    {
        return $this->getData("client_id");
    }

    /**
     * @return string
     */
    private function getSecret()
    {
        return $this->getData("secret");
    }
}
