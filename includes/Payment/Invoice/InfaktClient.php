<?php

namespace App\Payment\Invoice;

use App\Payment\General\BillingAddress;
use App\Requesting\Requester;
use App\Requesting\Response;

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

    /**
     * @throws InvoiceException
     */
    public function issue(BillingAddress $billingAddress, PurchaseItem $purchaseItem): string
    {
        $response = $this->requester->post(
            "https://api.infakt.pl/v3/invoices.json",
            json_encode([
                "invoice" => [
                    "client_company_name" => $billingAddress->getName(),
                    "client_country" => "pl",
                    "client_street" => $billingAddress->getStreet(),
                    "client_city" => $billingAddress->getCity(),
                    "client_post_code" => $billingAddress->getPostalCode(),
                    "client_tax_code" => $billingAddress->getVatID(),
                    "kind" => "vat",
                    "payment_method" => "tpay",
                    "services" => [
                        [
                            "flat_rate_tax_symbol" => $purchaseItem->getFlatRateTax(),
                            "gross_price" => $purchaseItem->getPrice()->asInt(),
                            "name" => $purchaseItem->getServiceName(),
                            "symbol" => $purchaseItem->getPKWiUSymbol(),
                            "tax_symbol" => $purchaseItem->getTaxRate(),
                        ],
                    ],
                ],
            ]),
            $this->getCommonHeaders()
        );
        $this->throwOnError($response);

        return $response->json()["id"];
    }

    /**
     * @throws InvoiceException
     */
    public function markAsPaid(string $invoiceID): void
    {
        $response = $this->requester->post(
            "https://api.infakt.pl/v3/invoices/{$invoiceID}/paid.json",
            json_encode([]),
            $this->getCommonHeaders()
        );
        $this->throwOnError($response);
    }

    /**
     * @throws InvoiceException
     */
    public function sendByEmail(string $invoiceID, string $email): void
    {
        $response = $this->requester->post(
            "https://api.infakt.pl/v3/invoices/{$invoiceID}/deliver_via_email.json",
            json_encode([
                "print_type" => "original",
                "locale" => "pl",
                "recipient" => $email,
            ]),
            $this->getCommonHeaders()
        );
        $this->throwOnError($response);
    }

    private function getCommonHeaders(): array
    {
        return [
            "Content-Type" => "application/json",
            "X-inFakt-ApiKey" => $this->apiKey,
        ];
    }

    /**
     * @throws InvoiceException
     */
    private function throwOnError(?Response $response): void
    {
        if (!$response) {
            throw new InvoiceException("Couldn't connect to infakt");
        }

        if (!$response->isOk()) {
            throw new InvoiceException("Invalid response code {$response->getStatusCode()}");
        }
    }
}
