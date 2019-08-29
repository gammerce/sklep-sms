<?php
namespace App\Services\Interfaces;

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
     * @return integer        value returned by function add_bought_service_info
     */
    public function purchase(Purchase $purchaseData);
}
