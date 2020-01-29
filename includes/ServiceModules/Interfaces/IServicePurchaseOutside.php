<?php
namespace App\ServiceModules\Interfaces;

use App\Http\Validation\Validator;
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
     * @param Purchase $purchase
     * @return Validator
     */
    public function purchaseDataValidate(Purchase $purchase);
}
