<?php

class Payment
{

	private $service;
	private $platform;

	/** @var PaymentModule|IPayment_Sms|IPayment_Transfer */
	public $payment_api;

	function __construct($payment_service, $platform = '')
	{
		global $heart, $lang;

		$this->service = $payment_service;
		$this->platform = strlen($platform) ? $platform : $_SERVER['HTTP_USER_AGENT'];

		// Tworzymy obiekt obslugujacy stricte weryfikacje
		$className = $heart->get_payment_api($this->service);
		$this->payment_api = $className ? new $className() : NULL;

		// API podanej usługi nie istnieje.
		if ($this->payment_api === NULL) {
			output_page($lang->sprintf($lang->payment['bad_service'], $this->service));
		}
	}

	/**
	 * @param string $sms_code
	 * @param int $tariff
	 * @param Entity_User $user
	 * @return array
	 */
	public function pay_sms($sms_code, $tariff, $user)
	{
		global $db, $settings, $lang, $lang_shop;

		// Serwis płatności nie obsługuje płatności SMS
		if (!$this->payment_api->data['sms']) {
			return array(
				'status' => "NO_SMS_SERVE",
				'text' => $lang->sms['info']['no_sms_serve']
			);
		}

		if (object_implements($this->payment_api, "IPayment_Sms")) {
			$sms_number = $this->payment_api->smses[$tariff]['number'];
			$sms_return = $this->payment_api->verify_sms($sms_code, $sms_number);
		} else {
			$sms_return['status'] = "NO_SMS_SERVE";
			// Nie przerywamy jeszcze, bo chcemy sprawdzic czy nie ma takiego SMSa do wykrozystania w bazie
		}

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

				log_info($lang_shop->sprintf($lang_shop->payment['remove_code_from_db'], $db_code['code'], $db_code['tariff']));
			}
		}

		if ($sms_return['status'] == "OK") {
			// Dodanie informacji o płatności sms
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
				"VALUES ('%s','%d','%d','%s','%s','%s','%s','%d')",
				array($sms_code, get_sms_cost($sms_number) / 2, ceil(get_sms_cost($sms_number) * $settings['vat']),
					$this->payment_api->data['sms_text'], $sms_number, $user->getLastIp(), $this->platform, $sms_return['free'] ? 1 : $db_code['free'])
			));

			$output['payment_id'] = $db->last_id();
		}
		// SMS został wysłany na błędny numer
		else if ($sms_return['status'] == "BAD_NUMBER" && isset($sms_return['tariff'])) {
			// Dodajemy kod do listy kodów do wykorzystania
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "sms_codes` " .
				"SET `code` = '%s', `tariff` = '%d', `free` = '0'",
				array($sms_code, $sms_return['tariff'])
			));

			log_info($lang_shop->sprintf($lang_shop->add_code_to_reuse, $sms_code, $sms_return['tariff'], $user->getUsername(), $user->getUid(), $user->getLastIp(), $tariff));
		} else if ($sms_return['status'] != "NO_SMS_SERVE") {
			log_info($lang_shop->sprintf($lang_shop->bad_sms_code_used, $user->getUsername(), $user->getUid(), $user->getLastIp(),
				$sms_code, $this->payment_api->data['sms_text'], $sms_number, $sms_return['status']));
		}

		switch (strtoupper($sms_return['status'])) {
			// Prawidłowy kod zwrotny
			case "OK":
				$output['text'] = $lang->sms['info']['ok'];
				break;
			// Nieprawidlowy kod zwrotny
			case "BAD_CODE":
				$output['text'] = $lang->sms['info']['bad_code'];
				break;
			// Nieprawidlowy kod zwrotny
			case "BAD_NUMBER":
				$output['text'] = $lang->sms['info']['bad_number'];
				break;
			// Błąd API
			case "BAD_API":
				$output['text'] = $lang->sms['info']['bad_api'];
				break;
			// Błędny email
			case "BAD_EMAIL":
				$output['text'] = $lang->sms['info']['bad_email'];
				break;
			// Nie podano wszystkich potrzebnych danych
			case "BAD_DATA":
				$output['text'] = $lang->sms['info']['bad_data'];
				break;
			// Błąd serwera weryfikującego kod
			case "SERVER_ERROR":
				$output['text'] = $lang->sms['info']['server_error'];
				break;
			// Blad konfiguracji uslugi
			case "SERVICE_ERROR":
				$output['text'] = $lang->sms['info']['service_error'];
				break;
			// Błąd
			case "ERROR":
				$output['text'] = $lang->sms['info']['error'];
				break;
			// Nie mozna sie polaczyc z serwerem sprawdzajacym
			case "NO_CONNECTION":
				$output['text'] = $lang->sms['info']['no_connection'];
				break;
			case "NO_SMS_SERVE":
				$output['text'] = $lang->sms['info']['no_sms_serve'];
				break;
			default:
				$output['text'] = if_isset($sms_return['text'], $lang->sms['info']['dunno']);
		}

		$output['status'] = $sms_return['status'];
		return $output;
	}

	/**
	 * @param Entity_Purchase $purchase_data
	 * @return array
	 */
	public function pay_transfer($purchase_data)
	{
		global $lang;

		// Serwis płatności nie obsługuje płatności przelewem
		if (!$this->payment_api->data['transfer']) {
			return array(
				'status' => "NO_TRANSFER_SERVE",
				'text' => $lang->no_transfer_serve
			);
		}

		if (!object_implements($this->payment_api, "IPayment_Transfer"))
			return array(
				'status' => "NO_TRANSFER_SERVE",
				'text' => $lang->no_transfer_serve
			);

		// Dodajemy extra info
		$purchase_data->user->setPlatform($this->platform);

		$serialized = serialize($purchase_data);
		$data_filename = time() . "-" . md5($serialized);
		file_put_contents(SCRIPT_ROOT . "data/transfers/" . $data_filename, $serialized);

		return array(
			'status' => "transfer",
			'text' => $lang->transfer_ok,
			'positive' => true,
			'data' => array('data' => $this->payment_api->prepare_transfer($purchase_data, $data_filename)) // Przygotowuje dane płatności transferem
		);
	}

	/**
	 * @param Entity_TransferFinalize $transfer_finalize
	 * @return bool
	 */
	public function transferFinalize($transfer_finalize)
	{
		global $heart, $db, $lang_shop;

		$result = $db->query($db->prepare(
			"SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " .
			"WHERE `id` = '%d'",
			array($transfer_finalize->getOrderid())
		));

		// Próba ponownej autoryzacji
		if ($db->num_rows($result))
			return false;

		// Nie znaleziono pliku z danymi
		if (!file_exists(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename())) {
			log_info($lang_shop->sprintf($lang_shop->transfer_no_data_file, $transfer_finalize->getOrderid()));
			return false;
		}

		/** @var Entity_Purchase $purchase_data */
		$purchase_data = unserialize(file_get_contents(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename()));

		// Dodanie informacji do bazy danych
		$db->query($db->prepare(
			"INSERT INTO `" . TABLE_PREFIX . "payment_transfer` " .
			"SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
			array($transfer_finalize->getOrderid(), $purchase_data->getPayment('cost'), $transfer_finalize->getTransferService(),
				$purchase_data->user->getLastIp(), $purchase_data->user->getPlatform())
		));
		unlink(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename());

		// Błędny moduł
		if (($service_module = $heart->get_service_module($purchase_data->getService())) === NULL) {
			log_info($lang_shop->sprintf($lang_shop->transfer_bad_module, $transfer_finalize->getOrderid(), $purchase_data->getService()));
			return false;
		}

		if (!object_implements($service_module, "IService_Purchase")) {
			log_info($lang_shop->sprintf($lang_shop->transfer_no_purchase, $transfer_finalize->getOrderid(), $purchase_data->getService()));
			return false;
		}

		// Dokonujemy zakupu
		$purchase_data->setPayment(array(
			'method' => 'transfer',
			'payment_id' => $transfer_finalize->getOrderid()
		));
		$bought_service_id = $service_module->purchase($purchase_data);

		log_info($lang_shop->sprintf($lang_shop->payment_transfer_accepted, $bought_service_id, $transfer_finalize->getOrderid(), $transfer_finalize->getAmount(),
			$transfer_finalize->getTransferService(), $purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));

		return true;
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

	/**
	 * @param bool $escape
	 * @return mixed
	 */
	public function get_sms_text($escape = false)
	{
		return $escape ? htmlspecialchars($this->payment_api->data['sms_text']) : $this->payment_api->data['sms_text'];
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