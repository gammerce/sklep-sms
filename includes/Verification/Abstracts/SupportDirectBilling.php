<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request $request
     * @return FinalizedPayment
     */
    public function finalizeDirectBilling(Request $request);
}
