<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Support\Money;
use Symfony\Component\HttpFoundation\Request;

interface SupportTransfer
{
    /**
     * @param Money $price
     * @param Purchase $purchase
     * @return array
     * @throws PaymentProcessingException
     */
    public function prepareTransfer(Money $price, Purchase $purchase);

    /**
     * @param Request $request
     * @return FinalizedPayment
     */
    public function finalizeTransfer(Request $request);
}
