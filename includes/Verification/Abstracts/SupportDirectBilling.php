<?php
namespace App\Verification\Abstracts;

use App\Models\Purchase;

interface SupportDirectBilling
{
    /**
     * @param Purchase $purchase
     * @param string $dataFilename
     * @return array
     */
    public function prepareDirectBilling(Purchase $purchase, $dataFilename);
}
