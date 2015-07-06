<?php

interface IServiceExecuteAction
{
	/**
	 * Wykonuje jakąś akcję, przydatne przy pobieraniu danych przez jQuery
	 * i funkcję fetch_data
	 *
	 * @param string $action - akcja do zrobienia
	 * @param array $data - Dane $_POST
	 * @return string
	 */
	public function execute_action($action, $data);
}