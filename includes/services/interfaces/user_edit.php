<?php

interface IServiceUserEdit
{

	/**
	 * Metoda sprawdza dane formularza, podczas edycji usługi gracza przez gracza
	 * i gdy wszystko jest okej, to ją edytuje.
	 *
	 * @param array $data Dane $_POST
	 * @param array $user_service Obecne dane edytowanej usługi
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się wyedytować usługę
	 */
	public function user_edit_user_service($data, $user_service);

}