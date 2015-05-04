<?php

interface IServiceAdminManageUserService
{
	/**
	 * Metoda sprawdza dane formularza podczas dodawania graczowi usługi w PA
	 * i gdy wszystko jest okej, to ją dodaje.
	 *
	 * @param array $data Dane $_POST
	 * @return array		'status'	=> id wiadomości,
	 *						'text'		=> treść wiadomości
	 *						'positive'	=> czy udało się dodać usługę
	 */
	public function admin_add_user_service($data);

	/**
	 * Metoda sprawdza dane formularza podczas edycji usługi gracza w PA
	 * i gdy wszystko jest okej, to ją edytuje.
	 *
	 * @param array $data Dane $_POST
	 * @param array $user_service Obecne dane edytowanej usługi
	 * @return array		'status'	=> id wiadomości,
	 *						'text'		=> treść wiadomości
	 *						'positive'	=> czy udało się wyedytować usługę
	 */
	public function admin_edit_user_service($data, $user_service);
}