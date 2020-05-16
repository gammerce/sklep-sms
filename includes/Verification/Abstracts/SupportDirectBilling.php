<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;

interface SupportDirectBilling
{
    /**
     * @param int $price
     * @param Purchase $purchase
     * @return PaymentResult
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
