<?php
namespace App\Payment\Interfaces;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\Transaction;

interface IChargeWallet
{
    /**
     * @param Purchase $purchase
     * @param array $body
     * @return void
     */
    public function setup(Purchase $purchase, array $body);

    /**
     * @param Transaction $transaction
     * @return string
     */
    public function getTransactionView(Transaction $transaction);

    /**
     * @param PaymentPlatform $paymentPlatform
     * @return array
     */
    public function getOptionView(PaymentPlatform $paymentPlatform);

    /**
     * @param Purchase $purchase
     * @return string
     */
    public function getPrice(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @return string
     */
    public function getQuantity(Purchase $purchase);
}
