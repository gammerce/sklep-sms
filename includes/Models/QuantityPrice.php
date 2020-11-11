<?php
namespace App\Models;

use App\Support\Money;

class QuantityPrice
{
    /** @var int */
    private $quantity;

    /** @var int|null */
    public $directBillingDiscount;

    /** @var Money|null */
    public $directBillingPrice;

    /** @var int|null */
    public $smsDiscount;

    /** @var Money|null */
    public $smsPrice;

    /** @var int|null */
    public $transferDiscount;

    /** @var Money|null */
    public $transferPrice;

    public function __construct($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
