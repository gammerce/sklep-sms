<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Models\FinalizedPayment;
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
use Symfony\Component\HttpFoundation\Request;

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

    public static function processDataFields(array $data)
    {
        // TODO Register webhook: CHECKOUT.ORDER.APPROVED
        return $data;
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        $price /= 100;

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
                "Authorization" => "Basic {$this->getCredentials()}",
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

    public function finalizeTransfer(Request $request)
    {
        $body = $request->request->all();

        $id = array_dot_get($body, "id");
        $purchaseUnits = array_dot_get($body, "resource.purchase_units", []);
        $purchaseUnit = $purchaseUnits[0];
        $transactionId = array_dot_get($purchaseUnit, "custom_id");
        $amount = price_to_int(array_dot_get($purchaseUnit, "amount.value"));

        $status = $this->isPaymentValid($request) && $this->capturePayment($id);

        return (new FinalizedPayment())
            ->setStatus($status)
            ->setOrderId($id)
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($transactionId)
            ->setExternalServiceId($id)
            ->setTestMode($this->isTestMode());
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isPaymentValid(Request $request)
    {
        $body = $request->request->all();
        $eventType = array_dot_get($body, "event_type");
        $status = array_dot_get($body, "resource.status");

        if ($eventType !== "CHECKOUT.ORDER.APPROVED") {
            $this->fileLogger->error("PayPal | Invalid event type", $body);
            return false;
        }

        if ($status !== "APPROVED") {
            $this->fileLogger->error("PayPal | Invalid resource status", $body);
            return false;
        }

        $response = $this->requester->post(
            "{$this->payPalDomain}/v1/notifications/verify-webhook-signature",
            [
                "auth_algo" => $request->headers->get("PAYPAL-AUTH-ALGO"),
                "cert_url" => $request->headers->get("PAYPAL-CERT-URL"),
                "transmission_id" => $request->headers->get("PAYPAL-TRANSMISSION-ID"),
                "transmission_sig" => $request->headers->get("PAYPAL-TRANSMISSION-SIG"),
                "transmission_time" => $request->headers->get("PAYPAL-TRANSMISSION-TIME"),
                "webhook_id" => $this->getWebhookId(),
                "webhook_event" => $body,
            ],
            [
                "Authorization" => "Basic {$this->getCredentials()}",
                "Content-Type" => "application/json",
            ]
        );
        $result = $response->json();

        if (array_get($result, "verification_status") !== "SUCCESS") {
            $this->fileLogger->error("PayPal | Signature verification failed", $body);
            return false;
        }

        return true;
    }

    /**
     * @param string $orderId
     * @return bool
     */
    private function capturePayment($orderId)
    {
        $response = $this->requester->post(
            "{$this->payPalDomain}/v2/checkout/orders/{$orderId}/capture",
            [],
            [
                "Authorization" => "Basic {$this->getCredentials()}",
                "Content-Type" => "application/json",
            ]
        );
        $result = $response->json();

        if (array_get($result, "status") !== "COMPLETED") {
            $this->fileLogger->error("PayPal | Order capture failed", $result);
            return false;
        }

        return true;
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

    /**
     * @return string
     */
    private function getWebhookId()
    {
        // TODO Implement it
        return "";
    }

    /**
     * @return bool
     */
    private function isTestMode()
    {
        return str_contains($this->payPalDomain, "sandbox");
    }
}
