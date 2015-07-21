<?php

abstract class Service
{

	const MODULE_ID = "";
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
	 * Metoda wywoływana, gdy usługa jest usuwana.
	 *
	 * @param integer $service_id ID usługi
	 */
	public function service_delete($service_id)
	{
	}

	/**
	 * Metoda wywoływana przy usuwaniu usługi użytkownika.
	 *
	 * @param array $user_service Dane o usłudze z bazy danych
	 * @return boolean
	 */
	public function user_service_delete($user_service)
	{
		return true;
	}

	/**
	 * Metoda wywoływana po usunięciu usługi gracza.
	 *
	 * @param array $user_service Dane o usłudze z bazy danych
	 */
	public function user_service_delete_post($user_service)
	{
	}

	/**
	 * Metoda powinna zwrócić, czy usługa ma być wyświetlana na stronie WWW.
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
	 * @return string    Description
	 */
	public function description_full_get()
	{
		global $templates;

		$file = "services/" . escape_filename($this->service['id']) . "_desc";
		$output = eval($templates->render($file, false, true, false));

		return $output;
	}

	public function description_short_get()
	{
		return $this->service['description'];
	}

	public function get_module_id() {
		return $this::MODULE_ID;
	}
}