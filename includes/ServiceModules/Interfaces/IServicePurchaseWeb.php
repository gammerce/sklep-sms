<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;
use App\Models\Transaction;

/**
 * Możliwość zakupu usługi przez stronę WWWW
 * Implementacja tego interfejsu powinna pociągnąć za sobą implementacje interfejsu:
 *    IServicePurchase
 */
interface IServicePurchaseWeb extends IServicePurchase
{
    /**
     * Metoda powinna zwracać formularz zakupu w postaci stringa
     *
     * @param array $query
     * @return string   - Formularz zakupu
     */
    public function purchaseFormGet(array $query);

    /**
     * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
     * i trzeba sprawdzić, czy są one prawidłowe
     *
     * @param Purchase $purchase
     * @param array $body
     */
    public function purchaseFormValidate(Purchase $purchase, array $body);

    /**
     * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
     *
     * @param Purchase $purchase
     *
     * @return string Szczegóły zamówienia
     */
    public function orderDetails(Purchase $purchase);

    /**
     * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
     *
     * @param string      $action Do czego zostaną te dane użyte ( email, web, payment_log )
     *  email - wiadomość wysłana na maila o zakupie usługi
     *  web - informacje wyświetlone na stronie WWW zaraz po zakupie
     *  payment_log - wpis w historii płatności
     * @param Transaction $transaction
     * @return string|array Informacje o zakupionej usłudze
     */
    public function purchaseInfo($action, Transaction $transaction);
}
