<?php
namespace App\Payment\Invoice;

use App\Payment\General\BillingAddress;

class InvoiceService
{
    private InfaktClient $infaktClient;

    public function __construct(InfaktClient $infaktClient)
    {
        $this->infaktClient = $infaktClient;
    }

    public function isConfigured(): bool
    {
        return $this->infaktClient->isConfigured();
    }

    /**
     * @throws InvoiceIssueException
     * @throws InvoiceServiceUnavailableException
     */
    public function create(BillingAddress $billingAddress, PurchaseItem $purchaseItem): string
    {
        if (!$this->infaktClient->isConfigured()) {
            throw new InvoiceServiceUnavailableException();
        }

        return $this->infaktClient->issue($billingAddress, $purchaseItem);

        // TODO Mark as paid
    }
}
