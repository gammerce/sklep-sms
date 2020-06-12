<?php
namespace App\Verification\PaymentModules;

use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;

class PayPal extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "paypal";
    private $payPalDomain = "https://api.sandbox.paypal.com";

    public static function getDataFields()
    {
        return [
            new DataField("client_id"),
            new DataField("secret"),
        ];
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        $credentials = base64_encode("{$this->getClientId()}:{$this->getSecret()}");

//        $response = $this->requester->post(
//            "{$this->payPalDomain}/v1/oauth2/token",
//            "grant_type=client_credentials",
//            [
//                "Authorization" => "Basic {$credentials}",
//            ]
//        );
//
//        $accessToken = array_get($response->json(), "access_token");

        $response = $this->requester->post(
            "{$this->payPalDomain}/v2/checkout/orders",
            json_encode([
                "intent"         => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "PLN",
                            "value"         => "100.00",
                        ],
                    ],
                ],
                "application_context" => [
                    "purchase_id" => $purchase->getId(),
                ],
//                "payer"          => [
//                    "payment_method" => "paypal",
//                ],
//                "transactions"   => [
//                    [
//                        "amount"      => [
//                            "total"    => "30.11",
//                            "currency" => "PLN", // TODO Get it from settings
//                        ],
//                        "description" => $purchase->getDescription(),
//                        "custom"      => $purchase->getId(),
//                    ],
//                ],
//                "redirect_urls"  => [
//                    "return_url" => $this->url->to("/page/payment_success"),
//                    "cancel_url" => $this->url->to("/page/payment_error"),
//                ],
            ]),
            [
                "Authorization" => "Basic $credentials",
                "Content-Type"  => "application/json",
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
                    "url"    => array_get($item, "href"),
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
