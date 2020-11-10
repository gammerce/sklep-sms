<?php
namespace App\Support;

class Money
{
    /** @var int */
    private $value;

    /**
     * @param Money|string|int|null $value
     */
    public function __construct($value)
    {
        if ($value instanceof Money) {
            $this->value = $value->asInt();
        } else {
            $this->value = (int) $value;
        }
    }

    /**
     * @param string|null $price
     * @return Money
     */
    public static function fromPrice($price)
    {
        return new Money(price_to_int($price));
    }

    /**
     * @return int
     */
    public function asInt()
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function asFloat()
    {
        return $this->value / 100.0;
    }

    /**
     * @return string
     */
    public function asPrice()
    {
        return number_format($this->asFloat(), 2);
    }

    /**
     * @param Money $money
     * @return bool
     */
    public function equals(Money $money)
    {
        return $this->value === $money->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->asPrice();
    }
}
