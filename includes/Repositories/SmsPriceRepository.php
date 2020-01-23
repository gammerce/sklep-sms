<?php
namespace App\Repositories;

class SmsPriceRepository
{
    private $prices = [
        50,
        100,
        200,
        300,
        400,
        500,
        600,
        700,
        800,
        900,
        1000,
        1100,
        1400,
        1600,
        1900,
        2000,
        2500,
        2600,
    ];

    public function all()
    {
        return $this->prices;
    }

    public function exists($price)
    {
        return in_array($price, $this->all(), true);
    }
}
