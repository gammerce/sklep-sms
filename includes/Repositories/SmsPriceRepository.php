<?php
namespace App\Repositories;

use App\Support\Money;

class SmsPriceRepository
{
    /** @var Money[] */
    private array $prices;

    public function __construct()
    {
        $this->prices = [
            new Money(50),
            new Money(100),
            new Money(200),
            new Money(300),
            new Money(400),
            new Money(500),
            new Money(600),
            new Money(700),
            new Money(800),
            new Money(900),
            new Money(1000),
            new Money(1100),
            new Money(1400),
            new Money(1600),
            new Money(1900),
            new Money(2000),
            new Money(2500),
            new Money(2600),
        ];
    }

    /**
     * @return Money[]
     */
    public function all()
    {
        return $this->prices;
    }

    /**
     * @param Money|null $price
     * @return bool
     */
    public function exists(Money $price = null)
    {
        return collect($this->all())->some(fn(Money $money) => $money->equal($price));
    }
}
