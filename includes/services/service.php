<?php

abstract class Service
{

	public $service = array();

	function __construct($service)
	{
		if (!is_array($service)) { // Podano błędne dane usługi
			$this->service = NULL;
			return;
		}

		$this->service = $service;
	}

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Zwraca dodatkowe pola do uzupełnienia
	 */
	public function service_extra_fields()
	{
	}

	/**
	 * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
	 * jak coś się jej nie spodoba to zwraca o tym info w tablicy
	 *
	 * @param array $data Dane $_POST
	 * @return array		'key'	=> DOM Element name
	 *						'value'	=> Error message
	 */
	public function manage_service_pre($data)
	{
	}

	/**
	 * Metoda zostaje wywołana po tym, jak  weryfikacja danych
	 * przesłanych w formularzu w PA przebiegła bezproblemowo
	 *
	 * @param array $data Dane $_POST
	 * @return array 'query_set' - data for SET in a query
	 */
	public function manage_service_post($data)
	{
	}

	/**
	 * Metoda odpowiedzialna za zwracanie fomularzy do różnych czynności
	 *
	 * @param string $form Id formularza, który ma zostać zwrócony
	 * @param array $data Dane $_POST
	 * @return string		Treść formularza
	 */
	public function get_form($form, $data=array())
	{
		return FALSE;
	}

	/**
	 * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
	 * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
	 *
	 * @param array $data user:
	 *							uid - id uzytkownika wykonującego zakupy
	 *							ip - ip użytkownika wykonującego zakupy
	 *							email - email -||-
	 *							platform - -||-
	 *						transaction:
	 *							method - sposób płatności
	 *							service - serwis mający obsłużyć płatność
	 *							[sms_code] - kod zwrotny sms
	 *						order:
	 *							... - dane zamówienia
	 *						tariff - koszt usługi ( taryfa )
	 * @return array		'status'	=> id wiadomości,
	 *						'text'		=> treść wiadomości
	 *						'positive'	=> czy udało się przeprowadzić zakup czy nie
	 */
	public function validate_purchase_data($data)
	{
		return FALSE;
	}

	/**
	 * Metoda zwraca informacje o zakupionej usłudze, szczegóły zakupu.
	 * Informacje są wyświetlane na stronie my_current_services
	 *
	 * @param array $user_service Dane o usłudze z bazy danych
	 * @param string $button_edit String przycisku do edycji usługi
	 * @return string		Informacje o zakupionej usłudze
	 */
	public function my_service_info($user_service, $button_edit)
	{
		return FALSE;
	}

	/**
	 * Metoda wywoływana, gdy usługa jest usuwana.
	 *
	 * @param integer $service_id ID usługi
	 */
	public function delete_service($service_id)
	{
	}

	/**
	 * Metoda wywoływana przy usuwaniu usługi gracza.
	 *
	 * @param array $player_service Dane o usłudze z bazy danych
	 * @return boolean
	 */
	public function delete_player_service($player_service)
	{
		return true;
	}

	/**
	 * Metoda wywoływana po usunięciu usługi gracza.
	 *
	 * @param array $player_service Dane o usłudze z bazy danych
	 */
	public function delete_player_service_post($player_service)
	{
	}

	/**
	 * Metoda zwraca, czy usługa ma być wyświetlana na stronie WWW.
	 */
	public function show_on_web()
	{
		if ($this->service !== NULL)
			return $this->service['data']['web'];

		return false;
	}

	/**
	 * Super krotki opis to 28 znakow, przeznaczony jest tylko na serwery
	 * Krotki opis, to 'description', krótki na strone WEB
	 * Pełny opis, to plik z opisem całej usługi
	 *
	 * @return string	Description
	 */
	public function get_full_description()
	{
		$file = "services/" . escape_filename($this->service['id']) . "_desc";

		eval("\$output = \"" . get_template($file, false, true, false) . "\";");

		return $output;
	}

	public function get_short_description()
	{
		return $this->service['description'];
	}

}