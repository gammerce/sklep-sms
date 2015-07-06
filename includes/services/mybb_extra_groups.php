<?php

$heart->register_service_module("mybb_extra_groups", "Dodatkowe Grupy (MyBB)", "ServiceMybbExtraGroups", "ServiceMybbExtraGroupsSimple");

class ServiceMybbExtraGroupsSimple extends Service implements IServiceManageService, IServiceCreateNew
{

	const MODULE_ID = "mybb_extra_groups";

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 */
	public function get_service_extra_fields()
	{
		eval("\$output = \"" . get_template("services/" . $this::MODULE_ID . "/extra_fields", 0, 1, 0) . "\";");
		return $output;
	}

	/**
	 * Metoda testuje dane przesłane przez formularz podczas dodawania nowej usługi w PA
	 * jak coś się jej nie spodoba to zwraca o tym info w tablicy
	 *
	 * @param array $data Dane $_POST
	 * @return array        'key'    => DOM Element name
	 *                        'value'    => Error message
	 */
	public function manage_service_pre($data)
	{
		global $lang;

		if (!strlen($_POST['groups_mybb']))
			$output['groups_mybb'] = $lang->field_no_empty;

		return $output;
	}

	/**
	 * Metoda zostaje wywołana po tym, jak  weryfikacja danych
	 * przesłanych w formularzu dodania nowej usługi w PA przebiegła bezproblemowo
	 *
	 * @param array $data Dane $_POST
	 * @return array (
	 *    'query_set' - array of query SET elements:
	 *        array(
	 *            'type'    => '%s'|'%d'|'%f'|'%c'|etc.
	 *            'column'	=> kolumna
	 *            'value'	=> wartość kolumny
	 *        )
	 */
	public function manage_service_post($data)
	{
		$extra_data['groups_mybb'] = trim($data['groups_mybb']);

		return array(
			'query_set'	=> array(
				array(
					'type'	=> '%s',
					'column'=> 'data',
					'value'	=> json_encode($extra_data)
				)
			)
		);
	}
}

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements IServicePurchase, IServicePurchaseWeb, IServiceAdminManageUserService,
	IServiceUserEdit, IServiceTakeOver, IServiceAdminServiceCodes
{

	/**
	 * Metoda sprawdza dane formularza podczas dodawania kodu na usługę w PA
	 *
	 * @param array $data Dane $_POST
	 * @return array 'key' (DOM element name) => 'value'
	 */
	public function admin_add_service_code_validate($data)
	{
		// TODO: Implement admin_add_service_code_validate() method.
	}

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania kodu na usługę
	 *
	 * @return string
	 */
	public function admin_get_form_add_service_code()
	{
		// TODO: Implement admin_get_form_add_service_code() method.
	}

	/**
	 * Metoda zwraca tablicę z danymi które zostaną dodane do bazy wraz z kodem na usługę
	 * można założyć że dane są już prawidłowo zweryfikowane przez metodę admin_add_service_code_validate
	 *
	 * @param $data
	 * @return array (
	 *        'server'    - integer,
	 *        'amount'    - double,
	 *        'tariff'    - integer,
	 *        'data'        - string
	 * )
	 */
	public function admin_add_service_code_insert($data)
	{
		// TODO: Implement admin_add_service_code_insert() method.
	}

	/**
	 * Metoda sprawdza dane formularza podczas dodawania graczowi usługi w PA
	 * i gdy wszystko jest okej, to ją dodaje.
	 *
	 * @param array $data Dane $_POST
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się dodać usługę
	 */
	public function admin_add_user_service($data)
	{
		// TODO: Implement admin_add_user_service() method.
	}

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
	public function admin_edit_user_service($data, $user_service)
	{
		// TODO: Implement admin_edit_user_service() method.
	}

	/**
	 * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
	 * podczas dodawania usługi gracza
	 *
	 * @return array
	 *    'text'    - treść html
	 *    'scripts'    - skrypty js do dodania
	 */
	public function admin_get_form_add_user_service()
	{
		// TODO: Implement admin_get_form_add_user_service() method.
	}

	/**
	 * Metoda powinna zwrócić dodatkowe pola usługi
	 * podczas jej edycji w PA
	 *
	 * @param array $player_service - dane edytowanej usługi
	 * @return string
	 */
	public function admin_get_form_edit_user_service($player_service)
	{
		// TODO: Implement admin_get_form_edit_user_service() method.
	}

	/**
	 * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
	 *
	 * @param array $data user:
	 *                            uid - id uzytkownika wykonującego zakupy
	 *                            ip - ip użytkownika wykonującego zakupy
	 *                            email - email -||-
	 *                            name - nazwa -||-
	 *                        transaction:
	 *                            method - sposób płatności
	 *                            payment_id - id płatności
	 *                        order:
	 *                            server - serwer na który ma być wykupiona usługa
	 *                            auth_data - dane rozpoznawcze gracza
	 *                            type - TYPE_NICK / TYPE_IP / TYPE_SID
	 *                            password - hasło do usługi
	 *                            amount - ilość kupionej usługi
	 *
	 * @return integer        value returned by function add_bought_service_info
	 */
	public function purchase($data)
	{
		// TODO: Implement purchase() method.
	}

	/**
	 * Metoda powinna zwracać formularz zakupu w postaci stringa
	 *
	 * @return string   - Formularz zakupu
	 */
	public function form_purchase_service()
	{
		// TODO: Implement form_purchase_service() method.
	}

	/**
	 * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
	 * i trzeba sprawdzić, czy są one prawidłowe
	 *
	 * @param array $data Dane $_POST
	 * @return array        'status'    => id wiadomości,
	 *                        'text'        => treść wiadomości
	 *                        'positive'    => czy udało się przeprowadzić zakup czy nie
	 */
	public function validate_purchase_form($data)
	{
		// TODO: Implement validate_purchase_form() method.
	}

	/**
	 * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
	 *
	 * @param array $data 'service',
	 *                        'server',
	 *                        'order'
	 *                          ...
	 *                        'user',
	 *                        'tariff',
	 *                        'cost_transfer'
	 * @return string        Szczegóły zamówienia
	 */
	public function order_details($data)
	{
		// TODO: Implement order_details() method.
	}

	/**
	 * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
	 *
	 * @param string $action Do czego zostaną te dane użyte ( email, web, payment_log )
	 *                            email - wiadomość wysłana na maila o zakupie usługi
	 *                            web - informacje wyświetlone na stronie WWW zaraz po zakupie
	 *                            payment_log - wpis w historii płatności
	 * @param array $data Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
	 * @return string        Informacje o zakupionej usłudze
	 */
	public function purchase_info($action, $data)
	{
		// TODO: Implement purchase_info() method.
	}

	/**
	 * Zwraca formularz przejęcia usługi
	 *
	 * @param $service_id - ID usługi do przejęcia
	 * @return string
	 */
	public function form_take_over_service($service_id)
	{
		// TODO: Implement form_take_over_service() method.
	}

	/**
	 * Sprawdza poprawność danych wprowadzonych w formularzu przejęcia usługi
	 * a jeżeli wszystko jest ok, to ją przejmuje
	 *
	 * @param $data - Dane $_POST
	 * @return array    'status'    => id wiadomości
	 *                  'text'      => treść wiadomości
	 *                  'positive'  => czy udało się przejąć usługę
	 */
	public function take_over_service($data)
	{
		// TODO: Implement take_over_service() method.
	}

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
	public function user_edit_user_service($data, $user_service)
	{
		// TODO: Implement user_edit_user_service() method.
	}
}