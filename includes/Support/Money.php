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
        if ($price === null || $price === "") {
            $price = 0;
        }

        // We do it that way because of the floating point issues
        $value = (int) str_replace(".", "", number_format($price, 2));

        return new Money($value);
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

    public function __toString()
    {
        return $this->asPrice();
    }
}
