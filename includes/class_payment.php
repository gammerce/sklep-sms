<?php

class Payment
{
	const SMS_NOT_SUPPORTED = 'sms_not_supported';
	const TRANSFER_NOT_SUPPORTED = 'transfer_not_supported';

	private $platform;

	/** @var PaymentModule|IPayment_Sms|IPayment_Transfer */
	private $payment_module;

	function __construct($payment_module_id, $platform = '')
	{
		global $heart, $lang;

		$this->platform = strlen($platform) ? $platform : $_SERVER['HTTP_USER_AGENT'];

		// Tworzymy obiekt obslugujacy stricte weryfikacje
		$className = $heart->get_payment_module($payment_module_id);
		$this->payment_module = $className ? new $className() : NULL;

		// API podanej usługi nie istnieje.
		if ($this->payment_module === NULL) {
			output_page($lang->sprintf($lang->payment['bad_service'], $payment_module_id));
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

		if (!$this->getPaymentModule()->supportSms()) {
			return array(
				'status' => Payment::SMS_NOT_SUPPORTED,
				'text' => $lang->sms['info'][Payment::SMS_NOT_SUPPORTED]
			);
		}

		if (object_implements($this->getPaymentModule(), "IPayment_Sms")) {
			$sms_number = $this->getPaymentModule()->getTariffById($tariff)->getNumber();
			$sms_return = $this->getPaymentModule()->verify_sms($sms_code, $sms_number);

			if (!is_array($sms_return)) {
				$sms_return['status'] = $sms_return;
			}
		} else {
			$sms_return['status'] = Payment::SMS_NOT_SUPPORTED;
			// Nie przerywamy jeszcze, bo chcemy sprawdzic czy nie ma takiego SMSa do wykorzystania w bazie
		}

		// Jezeli weryfikacja smsa nie zwrocila, ze kod zostal prawidlowo zweryfikowany
		// ani, że sms został wysłany na błędny numer,
		// to sprawdzamy czy kod jest w bazie kodów do wykorzystania
		if (!isset($sms_return) || !in_array($sms_return['status'], array(IPayment_Sms::BAD_NUMBER, IPayment_Sms::OK))) {
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
				$sms_return['status'] = IPayment_Sms::OK;

				log_info($lang_shop->sprintf($lang_shop->payment['remove_code_from_db'], $db_code['code'], $db_code['tariff']));
			}
		}

		if ($sms_return['status'] == IPayment_Sms::OK) {
			// Dodanie informacji o płatności sms
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
				"VALUES ('%s','%d','%d','%s','%s','%s','%s','%d')",
				array($sms_code, get_sms_cost($sms_number) / 2, ceil(get_sms_cost($sms_number) * $settings['vat']),
					$this->getPaymentModule()->getSmsCode(), $sms_number, $user->getLastIp(), $this->platform, $sms_return['free'] ? 1 : $db_code['free'])
			));

			$output['payment_id'] = $db->last_id();
		} // SMS został wysłany na błędny numer
		else if ($sms_return['status'] == IPayment_Sms::BAD_NUMBER && isset($sms_return['tariff'])) {
			// Dodajemy kod do listy kodów do wykorzystania
			$db->query($db->prepare(
				"INSERT INTO `" . TABLE_PREFIX . "sms_codes` " .
				"SET `code` = '%s', `tariff` = '%d', `free` = '0'",
				array($sms_code, $sms_return['tariff'])
			));

			log_info($lang_shop->sprintf($lang_shop->add_code_to_reuse, $sms_code, $sms_return['tariff'],
				$user->getUsername(), $user->getUid(), $user->getLastIp(), $tariff));
		} else if ($sms_return['status'] != Payment::SMS_NOT_SUPPORTED) {
			log_info($lang_shop->sprintf($lang_shop->bad_sms_code_used, $user->getUsername(), $user->getUid(), $user->getLastIp(),
				$sms_code, $this->getPaymentModule()->getSmsCode(), $sms_number, $sms_return['status']));
		}

		return array(
			'status' => $sms_return['status'],
			'text' => if_isset($sms_return['text'], if_isset($lang->sms['info'][$sms_return['status']], $sms_return['status']))
		);
	}

	/**
	 * @param Entity_Purchase $purchase_data
	 * @return array
	 */
	public function pay_transfer($purchase_data)
	{
		global $lang;

		if (!$this->getPaymentModule()->supportTransfer() || !object_implements($this->getPaymentModule(), "IPayment_Transfer")) {
			return array(
				'status' => Payment::TRANSFER_NOT_SUPPORTED,
				'text' => $lang->transfer[Payment::TRANSFER_NOT_SUPPORTED]
			);
		}

		// Dodajemy extra info
		$purchase_data->user->setPlatform($this->platform);

		$serialized = serialize($purchase_data);
		$data_filename = time() . "-" . md5($serialized);
		file_put_contents(SCRIPT_ROOT . "data/transfers/" . $data_filename, $serialized);

		return array(
			'status' => "transfer",
			'text' => $lang->transfer['prepared'],
			'positive' => true,
			'data' => array('data' => $this->getPaymentModule()->prepare_transfer($purchase_data, $data_filename)) // Przygotowuje dane płatności transferem
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

	/**
	 * @param bool $escape
	 * @return string
	 */
	public function getSmsCode($escape = false)
	{
		return $escape ? htmlspecialchars($this->getPaymentModule()->getSmsCode()) : $this->getPaymentModule()->getSmsCode();
	}

	public function get_platform()
	{
		return $this->platform;
	}

	/**
	 * @return IPayment_Sms|IPayment_Transfer|PaymentModule
	 */
	public function getPaymentModule()
	{
		return $this->payment_module;
	}

}