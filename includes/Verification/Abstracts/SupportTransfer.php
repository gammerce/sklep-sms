<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use Symfony\Component\HttpFoundation\Request;

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
     * @param Request $request
     * @return FinalizedPayment
     */
    public function finalizeTransfer(Request $request);
}
