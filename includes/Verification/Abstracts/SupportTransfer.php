<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;

interface SupportTransfer
{
    /**
     * @param int $price
     * @param Purchase $purchase
     * @return array
     * @throws PaymentProcessingException
     */
    public function prepareTransfer($price, Purchase $purchase);

    /**
     * @param array $query
     * @param array $body
     * @return FinalizedPayment
     */
    public function finalizeTransfer(array $query, array $body);
}
