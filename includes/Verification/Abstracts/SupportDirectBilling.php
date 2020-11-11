<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Support\Money;
use Symfony\Component\HttpFoundation\Request;

interface SupportDirectBilling
{
    /**
     * @param Money $price
     * @param Purchase $purchase
     * @return PaymentResult
     * @throws PaymentProcessingException
     */
    public function prepareDirectBilling(Money $price, Purchase $purchase);

    /**
     * @param Request $request
     * @return FinalizedPayment
     */
    public function finalizeDirectBilling(Request $request);
}
