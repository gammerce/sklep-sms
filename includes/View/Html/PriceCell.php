<?php
namespace App\View\Html;

class PriceCell extends Cell
{
    public function __construct($price)
    {
        parent::__construct($price, "price");
    }
}
