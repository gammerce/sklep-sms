<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;

/**
 * Możliwość zakupu usługi z zewnątrz ( np. z serwera )
 * Implementacja tego interfejsu powinna pociągnąć za sobą implementacje interfejsu:
 *    IServicePurchase
 */
interface IServicePurchaseOutside extends IServicePurchase
{
    /**
     * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
     * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
     *
     * @param Purchase $purchaseData
     *
     * @return array
     *  status => string id wiadomości,
     *  text => string treść wiadomości
     *  positive => bool czy udało się przeprowadzić zakup czy nie
     *  [data => array('warnings' => array())]
     *  [purchase_data => Entity_Purchase dane zakupu]
     */
    public function purchaseDataValidate(Purchase $purchaseData);
}
