<?php
namespace App\Repositories;

class SmsPriceRepository
{
    // TODO Add the rest
    private $prices = [50, 100];

    public function all()
    {
        return $this->prices;
    }

    public function exists($price)
    {
        return in_array($price, $this->all(), true);
    }
}
