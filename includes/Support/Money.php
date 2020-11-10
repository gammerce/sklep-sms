<?php
namespace App\Support;

class Money
{
    /** @var int */
    private $value;

    /**
     * @param int|Money $value
     */
    private function __construct($value)
    {
        if ($value instanceof Money) {
            $this->value = $value->asInt();
        } else {
            $this->value = (int) $value;
        }
    }

    /**
     * @param string|null $price
     * @return Money|null
     */
    public static function fromPrice($price)
    {
        if ($price === null || $price === "") {
            return null;
        }

        // We do it that way because of the floating point issues
        $value = (int) str_replace(".", "", number_format($price, 2));

        return new Money($value);
    }

    /**
     * @param int $value
     * @return Money|null
     */
    public static function fromInt($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

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
