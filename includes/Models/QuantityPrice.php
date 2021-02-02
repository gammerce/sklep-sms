<?php
namespace App\Models;

use App\Support\Money;

class QuantityPrice
{
    private int $quantity;
    public ?int $directBillingDiscount = null;
    public ?Money $directBillingPrice = null;
    public ?int $smsDiscount = null;
    public ?Money $smsPrice = null;
    public ?int $transferDiscount = null;
    public ?Money $transferPrice = null;

    public function __construct($quantity)
    {
        $this->quantity = $quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
