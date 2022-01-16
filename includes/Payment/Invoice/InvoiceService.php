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
     * @throws InvoiceException
     * @throws InvoiceServiceUnavailableException
     */
    public function create(
        BillingAddress $billingAddress,
        PurchaseItem $purchaseItem,
        ?string $email
    ): string {
        if (!$this->infaktClient->isConfigured()) {
            throw new InvoiceServiceUnavailableException();
        }

        $invoiceID = $this->infaktClient->issue($billingAddress, $purchaseItem);
        $this->infaktClient->markAsPaid($invoiceID);
        $this->infaktClient->sendByEmail($invoiceID, $email);

        return $invoiceID;
    }
}
