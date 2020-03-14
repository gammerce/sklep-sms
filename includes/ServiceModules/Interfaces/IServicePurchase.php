<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;

interface IServicePurchase
{
    /**
     * Method called when service has been purchased successfully
     *
     * @param Purchase $purchase
     * @return int ID of the BoughtService
     */
    public function purchase(Purchase $purchase);
}
