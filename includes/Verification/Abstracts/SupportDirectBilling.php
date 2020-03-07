<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;

interface SupportDirectBilling
{
    /**
     * @param Purchase $purchase
     * @param string $dataFilename
     * @return array
     */
    public function prepareDirectBilling(Purchase $purchase, $dataFilename);

    /**
     * @param array $query
     * @param array $body
     * @return FinalizedPayment
     */
    public function finalizeDirectBilling(array $query, array $body);
}
