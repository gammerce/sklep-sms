<?php

/**
 * Możliwość zakupu usługi z zewnątrz ( np. z serwera )
 * Implementacja tego interfejsu powinna pociągnąć za sobą implementacje interfejsu:
 *    IService_Purchase
 * Interface IService_PurchaseOutside
 */
interface IService_PurchaseOutside
{
    /**
     * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
     * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
     *
     * @param Entity_Purchase $purchase_data
     *
     * @return array
     *  status => string id wiadomości,
     *  text => string treść wiadomości
     *  positive => bool czy udało się przeprowadzić zakup czy nie
     *  [data => array('warnings' => array())]
     *  [purchase_data => Entity_Purchase dane zakupu]
     */
    public function purchase_data_validate($purchase_data);
}