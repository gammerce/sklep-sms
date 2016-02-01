<?php

/**
 * Możliwość zakupu usługi przez stronę WWWW
 * Implementacja tego interfejsu powinna pociągnąć za sobą implementacje interfejsu:
 *    IService_Purchase
 * Interface IService_PurchaseWeb
 */
interface IService_PurchaseWeb
{

	/**
	 * Metoda powinna zwracać formularz zakupu w postaci stringa
	 *
	 * @return string   - Formularz zakupu
	 */
	public function purchase_form_get();

	/**
	 * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
	 * i trzeba sprawdzić, czy są one prawidłowe
	 *
	 * @param array $post Dane $_POST
	 *
	 * @return array
	 *  status => string id wiadomości,
	 *  text => string treść wiadomości
	 *  positive => bool czy udało się przeprowadzić zakup czy nie
	 *  [data => array('warnings' => array())]
	 *  [purchase_data => Entity_Purchase dane zakupu]
	 */
	public function purchase_form_validate($post);

	/**
	 * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
	 *
	 * @param Entity_Purchase $purchase_data
	 *
	 * @return string Szczegóły zamówienia
	 */
	public function order_details($purchase_data);

	/**
	 * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
	 *
	 * @param string $action Do czego zostaną te dane użyte ( email, web, payment_log )
	 *  email - wiadomość wysłana na maila o zakupie usługi
	 *  web - informacje wyświetlone na stronie WWW zaraz po zakupie
	 *  payment_log - wpis w historii płatności
	 * @param array $data Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
	 *
	 * @return string|array Informacje o zakupionej usłudze
	 */
	public function purchase_info($action, $data);

}