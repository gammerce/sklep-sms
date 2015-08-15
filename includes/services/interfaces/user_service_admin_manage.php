<?php

/**
 * Obsługa wyświetlania trwających usług użytkowników w PA
 * (Ten interfejs powinien być implementowany w klasie *Simple modułu usługi)
 *
 * Interface IService_UserServiceAdminDisplay
 */
interface IService_UserServiceAdminDisplay
{
	public static function user_service_admin_display_subpageid_get();

	/**
	 * Zwraca tytuł strony, gdy włączona jest lista usług użytkowników
	 *
	 * @return string
	 */
	public function user_service_admin_display_title_get();

	/**
	 * Zwraca listę usług użytkowników.
	 * Na zwróconej tablicy wykonywana jest funkcja extract()
	 * a wygląd jest generowany przy użyciu pliku table_structure.html
	 * Jeżeli zwróci stringa, to jest on wyświetlany bez żadnej dodatkowej obróbki.
	 *
	 * @param array $get
	 * @param array $post
	 * @return array|string
	 */
	public function user_service_admin_display_get($get, $post);
}

/**
 * Obsługa dodawania usług użytkownika w PA
 *
 * Interface IService_UserServiceAdminAdd
 */
interface IService_UserServiceAdminAdd
{
	/**
	 * Metoda sprawdza dane formularza podczas dodawania użytkownikowi usługi w PA
	 * i gdy wszystko jest okej, to ją dodaje.
	 *
	 * @param array $data Dane $_POST
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się dodać usługę
	 */
	public function user_service_admin_add($data);

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania usługi użytkownikowi
	 *
	 * @return string
	 */
	public function user_service_admin_add_form_get();
}

/**
 * Obsługa edycji usług użytkownika w PA
 *
 * Interface IService_UserServiceAdminEdit
 */
interface IService_UserServiceAdminEdit
{
	/**
	 * Metoda sprawdza dane formularza podczas edycji usługi użytkownika w PA
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
	 * Metoda powinna zwrócić dodatkowe pola usługi
	 * podczas jej edycji w PA
	 *
	 * @param array $user_service	- dane edytowanej usługi
	 * @return string
	 */
	public function user_service_admin_edit_form_get($user_service);
}