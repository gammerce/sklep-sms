<?php

interface IService_UserServiceAdminManage
{
	/**
	 * Metoda sprawdza dane formularza podczas dodawania graczowi usługi w PA
	 * i gdy wszystko jest okej, to ją dodaje.
	 *
	 * @param array $data Dane $_POST
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się dodać usługę
	 */
	public function user_service_admin_add($data);

	/**
	 * Metoda sprawdza dane formularza podczas edycji usługi gracza w PA
	 * i gdy wszystko jest okej, to ją edytuje.
	 *
	 * @param array $data Dane $_POST
	 * @param array $user_service Obecne dane edytowanej usługi
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się wyedytować usługę
	 */
	public function user_service_admin_edit($data, $user_service);

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania usługi gracza
	 *
	 * @return string
	 */
	public function user_service_admin_add_form_get();

	/**
	 * Metoda powinna zwrócić dodatkowe pola usługi
	 * podczas jej edycji w PA
	 *
	 * @param array $user_service	- dane edytowanej usługi
	 * @return string
	 */
	public function user_service_admin_edit_form_get($user_service);
}