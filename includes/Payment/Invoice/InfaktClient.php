<?php

namespace App\Payment\Invoice;

use App\Payment\General\BillingAddress;
use App\Requesting\Requester;

class InfaktClient
{
    private Requester $requester;
    private string $apiKey;

    public function __construct(Requester $requester, string $apiKey)
    {
        $this->requester = $requester;
        $this->apiKey = $apiKey;
    }

    public function isConfigured(): bool
    {
        return strlen($this->apiKey) > 0;
    }

    public function issue(BillingAddress $billingAddress, PurchaseItem $purchaseItem): string
    {
        $response = $this->requester->post(
            "https://api.infakt.pl/v3/invoices.json",
            json_encode([
                "invoice" => [
                    "payment_method" => "tpay",
                    "client_company_name" => $billingAddress->getName(),
                    "client_country" => "pl",
                    "client_street" => $billingAddress->getStreet(),
                    "client_city" => $billingAddress->getCity(),
                    "client_post_code" => $billingAddress->getPostalCode(),
                    "client_tax_code" => $billingAddress->getVatID(),
                    "services" => [
                        [
                            "name" => $purchaseItem->getServiceName(),
                            "gross_price" => $purchaseItem->getPrice()->asInt(),
                            "tax_symbol" => $purchaseItem->getTaxRate(),
                        ],
                    ],
                ],
            ]),
            [
                "Content-Type" => "application/json",
                "X-inFakt-ApiKey" => $this->apiKey,
            ]
        );

        if (!$response) {
            throw new InvoiceIssueException("Couldn't connect to infakt");
        }

        if (!$response->isOk()) {
            throw new InvoiceIssueException("Invalid response code {$response->getStatusCode()}");
        }

        return $response->json()["id"];
    }

    public function markInvoiceAsPaid(string $invoiceID): void
    {
        //
    }
}
