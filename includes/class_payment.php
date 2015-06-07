<?php

class Payment
{

	private $service;
	private $platform;
	public $payment_api;

	function __construct($payment_service, $platform = "")
	{
		global $heart, $lang;

		$this->service = $payment_service;
		$this->platform = strlen($platform) ? $platform : $_SERVER['HTTP_USER_AGENT'];

		// Tworzymy obiekt obslugujacy stricte weryfikacje
		$className = $heart->get_payment_api($this->service);
		$this->payment_api = $className ? new $className() : NULL;

		// API podanej usługi nie istnieje.
		if ($this->payment_api === NULL) {
			output_page(newsprintf($lang['payment']['bad_service'], $this->service));
		}
	}

	public function pay_sms($sms_code, $tariff)
	{
		global $db, $user, $settings, $lang;

		// Serwis płatności nie obsługuje płatności SMS
		if (!$this->payment_api->data['sms']) {
			return array(
				'status' => "NO_SMS_SERVE",
				'text' => json_encode($this->payment_api->data)//$lang['sms']['info']['no_sms_serve']
			);
		}

		if (class_has_interface($this->payment_api, "IPaymentSMS")) {
			$sms_number = $this->payment_api->smses[$tariff]['number'];
			$sms_return = $this->payment_api->verify_sms($sms_code, $sms_number);
		}
		else // Nie przerywamy jeszcze, bo chcemy sprawdzic czy nie ma takiego SMSa do wykrozystania w bazie
			$sms_return['status'] = "NO_SMS_SERVE";

		// Jezeli weryfikacja smsa nie zwrocila, ze kod zostal prawidlowo zweryfikowany
		// ani, że sms został wysłany na błędny numer,
		// to sprawdzamy czy kod jest w bazie kodów do wykorzystania
		if (!isset($sms_return) || !in_array($sms_return['status'], array("BAD_NUMBER", "OK"))) {
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "sms_codes` " .
				"WHERE `code` = '%s' AND `tariff` = '%d'",
				array($sms_code, $tariff)
			));

			// Jest taki kod w bazie
			if ($db->num_rows($result)) {
				$db_code = $db->fetch_array_assoc($result);

				// Usuwamy kod z listy kodow do wykorzystania
				$db->query($db->prepare(
					"DELETE FROM `" . TABLE_PREFIX . "sms_codes` " .
					"WHERE `id` = '%d'",
					array($db_code['id'])
				));
				// Ustawienie wartości, jakby kod był prawidłowy
				$sms_return['status'] = "OK";

				log_info(newsprintf($lang['payment']['remove_code_from_db'], $db_code['code'], $db_code['tariff']));
			}
		}

		if ($sms_return['status'] == "OK") {
			// Dodanie informacji o płatności sms
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
				"VALUES ('%s','%.2f','%.2f','%s','%s','%s','%s','%d')",
				array($sms_code, get_sms_cost($sms_number) / 2.0, number_format(get_sms_cost($sms_number) * $settings['vat'], 2),
					$this->payment_api->data['sms_text'], $sms_number, $user['ip'], $this->platform, $db_code['free'])
			));

			$output['payment_id'] = $db->last_id();
		} // SMS został wysłany na błędny numer
		else if ($sms_return['status'] == "BAD_NUMBER" && isset($sms_return['tariff'])) {
			// Dodajemy kod do listy kodów do wykorzystania
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "sms_codes` " .
				"SET `code` = '%s', `tariff` = '%d', `free` = '0'",
				array($sms_code, $sms_return['tariff'])
			));

			log_info(newsprintf($lang['add_code_to_reuse'], $sms_code, $sms_return['tariff'], $user['username'], $user['uid'], $user['ip'], $tariff));
		} else if( $sms_return['status'] != "NO_SMS_SERVE" )
			log_info(newsprintf($lang['bad_sms_code_used'], $user['username'], $user['uid'], $user['ip'], $sms_code,
				$this->payment_api->data['sms_text'], $sms_number, $sms_return['status']));

		switch ($sms_return['status']) {
			// Prawidłowy kod zwrotny
			case "OK":
				$output['text'] = $lang['sms']['info']['ok'];
				break;
			// Nieprawidlowy kod zwrotny
			case "BAD_CODE":
				$output['text'] = $lang['sms']['info']['bad_code'];
				break;
			// Nieprawidlowy kod zwrotny
			case "BAD_NUMBER":
				$output['text'] = $lang['sms']['info']['bad_number'];
				break;
			// Błąd API
			case "BAD_API":
				$output['text'] = $lang['sms']['info']['bad_api'];
				break;
			// Błędny email
			case "BAD_EMAIL":
				$output['text'] = $lang['sms']['info']['bad_email'];
				break;
			// Nie podano wszystkich potrzebnych danych
			case "BAD_DATA":
				$output['text'] = $lang['sms']['info']['bad_data'];
				break;
			// Błąd serwera weryfikującego kod
			case "SERVER_ERROR":
				$output['text'] = $lang['sms']['info']['server_error'];
				break;
			// Blad konfiguracji uslugi
			case "SERVICE_ERROR":
				$output['text'] = $lang['sms']['info']['service_error'];
				break;
			// Błąd
			case "ERROR":
				$output['text'] = $lang['sms']['info']['error'];
				break;
			// Nie mozna sie polaczyc z serwerem sprawdzajacym
			case "NO_CONNECTION":
				$output['text'] = $lang['sms']['info']['no_connection'];
				break;
			case "NO_SMS_SERVE":
				$output['text'] = $lang['sms']['info']['no_sms_serve'];
				break;
			default:
				$output['text'] = if_isset($output['text'], $lang['sms']['info']['dunno']);
		}

		$output['status'] = $sms_return['status'];
		return $output;
	}

	public function pay_transfer($data)
	{
		global $user, $lang;

		// Serwis płatności nie obsługuje płatności przelewem
		if (!$this->payment_api->data['transfer']) {
			return array(
				'status' => "NO_TRANSFER_SERVE",
				'text' => $lang['no_transfer_serve']
			);
		}

		if (!class_has_interface($this->payment_api, "IPaymentTransfer"))
			return array(
				'status' => "NO_TRANSFER_SERVE",
				'text' => $lang['no_transfer_serve']
			);

		// Dodajemy extra info
		$data['platform'] = $this->platform;
		$data['uid'] = $user['uid'];
		$data['ip'] = $user['ip'];
		$data['forename'] = $user['forename'];
		$data['surname'] = $user['surname'];

		return array(
			'status' => "transfer",
			'text' => $lang['transfer_ok'],
			'positive' => true,
			'data' => array('data' => $this->payment_api->prepare_transfer($data)) // Przygotowuje dane płatności transferem
		);
	}

	public function get_provision_by_tariff($tariff)
	{
		return $this->payment_api->smses[$tariff]['provision'];
	}

	public function get_provision_by_number($number)
	{
		if (!isset($this->payment_api->smses[$number]))
			return 0;

		return $this->payment_api->smses[$number]['provision'];
	}

	public function get_number_by_tariff($tariff)
	{
		if (!isset($this->payment_api->smses[$tariff]))
			return "";

		return $this->payment_api->smses[$tariff]['number'];
	}

	public function get_sms_text()
	{
		return $this->payment_api->data['sms_text'];
	}

	public function get_sms_text_hsafe()
	{
		return $this->payment_api->data['sms_text_hsafe'];
	}

	public function get_payment_service()
	{
		return $this->service;
	}

	public function get_platform()
	{
		return $this->platform;
	}

	public function transfer_available()
	{
		return $this->payment_api->data['transfer'];
	}

	public function sms_available()
	{
		return $this->payment_api->data['sms'];
	}

}