<?php

interface IPaymentTransfer {

	/**
	 * Przygotowanie zapytania POST w celu płatności przelewem
	 *
	 * @param array $data
	 *			   ['type'] - typ zakupu: TYPE_NICK, TYPE_IP, TYPE_SID
	 *			   ['server'] - na jakim serwerze została usługa zakupiona
	 *			   ['service'] - jaka usługa została zakupiona
	 *			   ['auth_data'] - dane gracza
	 *			   ['password'] - hasło gracza
	 *			   ['amount']	- ilosc zakupionej usługi
	 *			   ['cost'] - kwota przelewu
	 *			   ['uid'] - id uzytkownika
	 *			   ['ip'] - ip użytkownika
	 *			   ['forename'] - imie klienta
	 *			   ['surname'] - nazwisko klienta
	 *			   ['email'] - email klienta
	 *			   ['platform'] - skad zostal wykonnany przelew
	 *			   ['desc'] - opis płatności
	 * @return array	['url'] - adres url strony do ktorej wysylamy dane POST
	 *				  ...	 - pola POST
	 */
	public function prepare_transfer($data);

}