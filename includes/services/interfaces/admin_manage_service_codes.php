<?php

interface IServiceAdminServiceCodes
{
	/**
	 * Metoda sprawdza dane formularza podczas dodawania kodu na usługę w PA
	 *
	 * @param array $data 	Dane $_POST
	 * @return array 'key' (DOM element name) => 'value'
	 */
	public function admin_add_service_code_validate($data);

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania kodu na usługę
	 *
	 * @return string
	 */
	public function admin_get_form_add_service_code();

	/**
	 * Metoda zwraca tablicę z danymi które zostaną dodane do bazy wraz z kodem na usługę
	 * można założyć że dane są już prawidłowo zweryfikowane przez metodę admin_add_service_code_validate
	 *
	 * @param $data
	 * @return array (
	 * 		'server'	- integer,
	 * 		'amount'	- double,
	 * 		'tariff'	- integer,
	 * 		'data'		- string
	 * )
	 */
	public function admin_add_service_code_insert($data);
}