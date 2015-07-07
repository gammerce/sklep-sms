<?php

$heart->register_service_module("mybb_extra_groups", "Dodatkowe Grupy (MyBB)", "ServiceMybbExtraGroups", "ServiceMybbExtraGroupsSimple");

class ServiceMybbExtraGroupsSimple extends Service implements IService_AdminManage, IService_Create
{

	const MODULE_ID = "mybb_extra_groups";

	/**
	 * Metoda wywoływana przy edytowaniu lub dodawaniu usługi w PA
	 * Powinna zwracać dodatkowe pola do uzupełnienia
	 */
	public function service_admin_extra_fields_get()
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
	public function service_admin_manage_pre($data)
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
	public function service_admin_manage_post($data)
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

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements IService_Purchase, IService_PurchaseWeb
{
	/**
	 * Metoda powinna zwracać formularz zakupu w postaci stringa
	 *
	 * @return string   - Formularz zakupu
	 */
	public function purchase_form_get()
	{

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
	public function purchase_form_validate($data)
	{
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
}