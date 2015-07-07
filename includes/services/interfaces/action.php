<?php

interface IService_ActionExecute
{
	/**
	 * Wykonuje jakąś akcję, przydatne przy pobieraniu danych przez jQuery
	 * i funkcję fetch_data
	 *
	 * @param string $action - akcja do zrobienia
	 * @param array $data - Dane $_POST
	 * @return string
	 */
	public function service_action_execute($action, $data);
}