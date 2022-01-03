<?php
namespace App\Payment\Invoice;

use App\Payment\General\BillingAddress;
use App\Support\Money;

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
    public function create(
        BillingAddress $billingAddress,
        string $serviceName,
        Money $servicePrice
    ): void {
        $this->infaktClient->issue($billingAddress, $serviceName, $servicePrice);
    }
}
