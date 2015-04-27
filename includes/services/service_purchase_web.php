<?php

interface IServicePurchaseWeb
{

    /**
     * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
     * i trzeba sprawdzić, czy są one prawidłowe
     *
     * @param array $data Dane $_POST
     * @return array        'status'    => id wiadomości,
     *                        'text'        => treść wiadomości
     *                        'positive'    => czy udało się przeprowadzić zakup czy nie
     */
    public function validate_purchase_form($data);
    // TODO: Dodac checki w kodzie

    /**
     * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
     *
     * @param array $data 'service',
     *                        'server',
     *                        'order'
     *                          ...
     *                        'user',
     *                        'tariff',
     *                        'cost_transfer'
     * @return string        Szczegóły zamówienia
     */
    public function order_details($data);
    // TODO: Dodac checki w kodzie

    /**
     * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
     *
     * @param string $action    Do czego zostaną te dane użyte ( email, web, payment_log )
     *                            email - wiadomość wysłana na maila o zakupie usługi
     *                            web - informacje wyświetlone na stronie WWW zaraz po zakupie
     *                            payment_log - wpis w historii płatności
     * @param array $data   Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
     * @return string        Informacje o zakupionej usłudze
     */
    public function purchase_info($action, $data);

}