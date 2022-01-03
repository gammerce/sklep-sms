<?php

namespace App\Payment\Invoice;

use App\Payment\General\BillingAddress;
use App\Requesting\Requester;
use App\Support\Money;

class InfaktClient
{
    private Requester $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function issue(
        BillingAddress $billingAddress,
        string $serviceName,
        Money $servicePrice
    ): string {
        $response = $this->requester->post("https://api.infakt.pl/v3/invoices.json", [
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
                        "name" => $serviceName,
                        "gross_price" => $servicePrice->asInt(),
                        "tax_symbol" => 8,
                    ],
                ],
            ],
        ]);

        if (!$response || !$response->isOk()) {
            throw new InvoiceIssueException($response);
        }

        return $response->json()["id"];
    }

    public function markInvoiceAsPaid(string $invoiceID): void
    {
    }
}
