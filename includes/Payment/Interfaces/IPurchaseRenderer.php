<?php
namespace App\Payment\Interfaces;

use App\Models\Purchase;

interface IPurchaseRenderer
{
    /**
     * @param Purchase $purchase
     * @return string
     */
    public function render(Purchase $purchase);

    /**
     * @param Purchase $purchase
     * @return bool
     */
    public function isAvailable(Purchase $purchase);
}
