<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;

interface SupportTransfer
{
    /**
     * @return string
     */
    public static function getName();

    /**
     * @param int $price
     * @param Purchase $purchase
     * @return array
     */
    public function prepareTransfer($price, Purchase $purchase);

    /**
     * @param array $query
     * @param array $body
     * @return FinalizedPayment
     */
    public function finalizeTransfer(array $query, array $body);
}
