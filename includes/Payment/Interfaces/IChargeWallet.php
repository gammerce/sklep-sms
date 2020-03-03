<?php
namespace App\Payment\Interfaces;

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
}
