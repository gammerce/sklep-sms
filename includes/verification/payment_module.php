<?php

abstract class PaymentModule
{

	const SERVICE_ID = '';

	public $data = array();
	public $smses = array();
	public $sms_list = array();

	function __construct()
	{
		global $db;

		$result = $db->query($db->prepare(
			"SELECT `name`,`data`,`data_hidden`,`sms`,`transfer` ".
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' ",
			array($this::SERVICE_ID)
		));

		if (!$db->num_rows($result))
			output_page("Błąd w konstruktorze klasy API_*: Brak serwisu płatności ( " . $this::SERVICE_ID . " ) w bazie.");

		$row = $db->fetch_array_assoc($result);

		$this->data['name'] = $row['name'];
		$this->data['sms'] = $row['sms'];
		$this->data['transfer'] = $row['transfer'];

		$data = json_decode($row['data']);
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}
		$data_hidden = json_decode($row['data_hidden']);
		foreach ($data_hidden as $key => $value) {
			$this->data[$key] = $value;
		}

		if (isset($this->data['sms_text'])) {
			$this->data['sms_text_hsafe'] = htmlspecialchars($this->data['sms_text']);

			//
			// Pobieranie SMSow - numer, taryfa

			$result = $db->query($db->prepare(
				"SELECT sn.tariff AS `tariff`, sn.number AS `number`, t.provision AS `provision` " .
				"FROM `" . TABLE_PREFIX . "sms_numbers` AS sn " .
				"JOIN `" . TABLE_PREFIX . "tariffs` AS t ON t.tariff = sn.tariff " .
				"WHERE `service` = '%s' ",
				array($this::SERVICE_ID)
			));
			while ($row = $db->fetch_array_assoc($result)) {
				$this->sms_list[] = $row;
				$this->smses[$row['number']] = $row;
				$this->smses[$row['tariff']] = $row;
			}
		}
	}

}