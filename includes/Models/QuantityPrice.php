<?php
namespace App\Models;

class QuantityPrice
{
    /** @var int */
    private $quantity;

    /** @var int|null */
    public $directBillingDiscount;

    /** @var int|null */
    public $directBillingPrice;

    /** @var int|null */
    public $smsDiscount;

    /** @var int|null */
    public $smsPrice;

    /** @var int|null */
    public $transferDiscount;

    /** @var int|null */
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
