<?php
namespace App\Payment\Interfaces;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\Transaction;

interface IChargeWallet
{
    public function setup(Purchase $purchase, array $body): void;
    public function getTransactionView(Transaction $transaction): string;
    public function getOptionView(PaymentPlatform $paymentPlatform): array;
    public function getPrice(Purchase $purchase): string;
    public function getQuantity(Purchase $purchase): string;
}
