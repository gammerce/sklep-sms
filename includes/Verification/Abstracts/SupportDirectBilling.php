<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Support\Result;

interface SupportDirectBilling
{
    /**
     * @param int $price
     * @param Purchase $purchase
     * @return Result
     * @throws PaymentProcessingException
     */
    public function prepareDirectBilling($price, Purchase $purchase);

    /**
     * @param array $query
     * @param array $body
     * @return FinalizedPayment
     */
    public function finalizeDirectBilling(array $query, array $body);
}
