<?php

/**
 * Ten interfejs powinien być implementowany w klasie *Simple usługi
 *
 * Interface IServiceManageService
 */
interface IServiceManageService {

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 */
	public function get_service_extra_fields();

	/**
	 * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
	 * jak coś się jej nie spodoba to zwraca o tym info w tablicy
	 *
	 * @param array $data Dane $_POST
	 * @return array        'key'    => DOM Element name
	 *                        'value'    => Error message
	 */
	public function manage_service_pre($data);

	/**
	 * Metoda zostaje wywołana po tym, jak  weryfikacja danych
	 * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
	 *
	 * @param array $data Dane $_POST
	 * @return array (
	 * 	'query_set' - array of query SET elements:
	 * 		array(
	 * 			'type'	=> '%s'|'%d'|'%f'|'%c'|etc.
	 * 			'column'=> kolumna
	 * 			'value'	=> wartość kolumny
	 * 		)
	 */
	public function manage_service_post($data);

}