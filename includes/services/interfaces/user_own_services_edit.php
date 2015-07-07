<?php

interface IService_UserOwnServicesEdit
{

	/**
	 * Metoda powinna zwrócić formularz do edycji danych usługi przez gracza.
	 *
	 * @param $user_service		Dane edytowanej usługi
	 * @return string
	 */
	public function user_own_service_edit_form_get($user_service);

	/**
	 * Metoda sprawdza dane formularza, podczas edycji usługi użytkownika przez użytkownika
	 * i gdy wszystko jest okej, to ją edytuje.
	 *
	 * @param array $data Dane $_POST
	 * @param array $user_service Obecne dane edytowanej usługi
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się wyedytować usługę
	 */
	public function user_own_service_edit($data, $user_service);

}