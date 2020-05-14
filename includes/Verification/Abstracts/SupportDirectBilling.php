<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Support\Result;

interface SupportDirectBilling
{
    /**
     * @param Purchase $purchase
     * @return Result
     */
    public function prepareDirectBilling(Purchase $purchase);

    /**
     * @param array $query
     * @param array $body
     * @return FinalizedPayment
     */
    public function finalizeDirectBilling(array $query, array $body);
}
