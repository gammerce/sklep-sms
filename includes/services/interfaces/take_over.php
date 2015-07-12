<?php

interface IService_TakeOver
{

	/**
	 * Zwraca formularz przejęcia usługi
	 *
	 * @param $service_id - ID usługi do przejęcia
	 * @return string
	 */
	public function service_take_over_form_get($service_id);

	/**
	 * Sprawdza poprawność danych wprowadzonych w formularzu przejęcia usługi
	 * a jeżeli wszystko jest ok, to ją przejmuje
	 *
	 * @param $data - Dane $_POST
	 * @return array    'status'    => id wiadomości
	 *                  'text'      => treść wiadomości
	 *                  'positive'  => czy udało się przejąć usługę
	 */
	public function service_take_over($data);

}