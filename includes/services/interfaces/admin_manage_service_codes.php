<?php

interface IServiceAdminManageServiceCodes
{
	/**
	 * Metoda sprawdza dane formularza podczas dodawania kodu na usługę w PA
	 *
	 * @param array $data 	Dane $_POST
	 * @return array 'key' (DOM element name) => 'value'
	 */
	public function validate_admin_add_service_code($data);

	/**
	 * Metoda sprawdza dane formularza podczas edycji kodu na usługę w PA
	 *
	 * @param array $data 	Dane $_POST
	 * @param array $user_service 	Obecne dane edytowanego kodu
	 * @return array 'key' (DOM element name) => 'value'
	 */
	public function validate_admin_edit_service_code($data, $user_service);

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania kodu na usługę
	 *
	 * @return string
	 */
	public function admin_get_form_add_service_code();
}