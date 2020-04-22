<?php
namespace App\Models;

class QuantityPrice
{
    /** @var int */
    private $quantity;

    /** @var int */
    public $directBillingDiscount;

    /** @var int */
    public $directBillingPrice;

    /** @var int */
    public $smsDiscount;

    /** @var int */
    public $smsPrice;

    /** @var int */
    public $transferDiscount;

    /** @var int */
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
