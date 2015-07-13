<?php

interface IService_PurchaseOutside
{
	/**
	 * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
	 * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
	 *
	 * @param Entity_Purchase $purchase
	 *
	 * @return array          string 'status' - id wiadomości,
	 *                        string'text' - treść wiadomości
	 *                        boolean 'positive' - czy udało się przeprowadzić zakup czy nie
	 *                        Entity_Purchase 'purchase_data'
	 */
	public function purchase_data_validate($purchase);
}