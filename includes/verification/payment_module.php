<?php

abstract class PaymentModule
{

	const SERVICE_ID = '';

	/** @var  string */
	private $name;

	/** @var  bool */
	private $support_sms = false;

	/** @var  bool */
	private $support_transfer = false;

	/**
	 * Data from columns: data & data_hidden
	 *
	 * @var array
	 */
	private $data = array();

	public $smses = array();
	public $sms_list = array();

	function __construct()
	{
		global $db;

		$result = $db->query($db->prepare(
			"SELECT `name`, `data`, `data_hidden`, `sms`, `transfer` " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' ",
			array($this::SERVICE_ID)
		));

		if (!$db->num_rows($result))
			output_page("An error occured in class: " . get_class($this) . " constructor. There is no " . $this::SERVICE_ID . " payment service in database.");

		$row = $db->fetch_array_assoc($result);

		$this->name = $row['name'];
		$this->support_sms = (bool)$row['sms'];
		$this->support_transfer = (bool)$row['transfer'];

		$data = (array)json_decode($row['data'], true);
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}

		$data_hidden = (array)json_decode($row['data_hidden'], true);
		foreach ($data_hidden as $key => $value) {
			$this->data[$key] = $value;
		}

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

	/**
	 * @return boolean
	 */
	public function supportTransfer()
	{
		return $this->support_transfer;
	}

	/**
	 * @return boolean
	 */
	public function supportSms()
	{
		return $this->support_sms;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

}