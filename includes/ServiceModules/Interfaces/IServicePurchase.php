<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;

/**
 * Możliwość zakupu usługi
 */
interface IServicePurchase
{
    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param Purchase $purchaseData
     *
     * @return integer        value returned by function addBoughtServiceInfo
     */
    public function purchase(Purchase $purchaseData);
}
