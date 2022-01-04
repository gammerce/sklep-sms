<?php
namespace App\Payment\Invoice;

use App\Models\PurchaseItem;
use App\Payment\General\BillingAddress;

class InvoiceService
{
    private InfaktClient $infaktClient;

    public function __construct(InfaktClient $infaktClient)
    {
        $this->infaktClient = $infaktClient;
    }

    /**
     * @throws InvoiceIssueException
     */
    public function create(BillingAddress $billingAddress, PurchaseItem $purchaseItem): string
    {
        return $this->infaktClient->issue($billingAddress, $purchaseItem);

        // TODO Mark as paid
    }
}
