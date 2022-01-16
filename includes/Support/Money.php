<?php
namespace App\Support;

class Money
{
    private int $value;

    /**
     * @param Money|string|float|int|null $value
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
    public static function fromPrice($price): Money
    {
        return new Money(price_to_int($price));
    }

    public function asInt(): int
    {
        return $this->value;
    }

    public function asFloat(): float
    {
        return $this->value / 100.0;
    }

    public function asPrice(): string
    {
        return number_format($this->asFloat(), 2);
    }

    /**
     * @param Money|int $money
     * @return bool
     */
    public function equal($money): bool
    {
        if ($money instanceof Money) {
            return $money->asInt() === $this->value;
        }

        return $this->value === $money;
    }

    /**
     * @param Money|int $money
     * @return bool
     */
    public function notEqual($money): bool
    {
        return !$this->equal($money);
    }

    /**
     * @param Money|int $money
     * @return Money
     */
    public function add($money): Money
    {
        if ($money instanceof Money) {
            $money = $money->asInt();
        }
        return new Money($this->value + $money);
    }

    public function multiply(float $multiplier): Money
    {
        return new Money($this->value * $multiplier);
    }

    public function __toString()
    {
        return $this->asPrice();
    }
}
