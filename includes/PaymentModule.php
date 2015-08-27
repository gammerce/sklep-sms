<?php

abstract class PaymentModule
{

	const SERVICE_ID = '';

	/** @var  string */
	protected $name;

	/** @var  bool */
	protected $support_sms = false;

	/** @var  bool */
	protected $support_transfer = false;

	/**
	 * Data from columns: data & data_hidden
	 *
	 * @var array
	 */
	protected $data = array();

	/** @var Entity_Tariff[] */
	protected $tariffs = array();

	function __construct()
	{
		global $db;

		$result = $db->query($db->prepare(
			"SELECT `name`, `data`, `data_hidden`, `sms`, `transfer` " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"WHERE `id` = '%s' ",
			array($this::SERVICE_ID)
		));

		if (!$db->num_rows($result)) {
			output_page("An error occured in class: " . get_class($this) . " constructor. There is no " . $this::SERVICE_ID . " payment service in database.");
		}

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

		// Pozyskujemy taryfy
		$result = $db->query($db->prepare(
			"SELECT t.tariff, t.provision, t.predefined, sn.number " .
			"FROM `" . TABLE_PREFIX . "tariffs` AS t " .
			"LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON t.tariff = sn.tariff " .
			"WHERE sn.service = '%s' ",
			array($this::SERVICE_ID)
		));

		while ($row = $db->fetch_array_assoc($result)) {
			$tariff = new Entity_Tariff($row['tariff'], $row['provision'], $row['predefined'], $row['number']);

			$this->tariffs[$tariff->getId()] = $tariff;

			if ($tariff->getNumber() !== NULL)
				$this->tariffs[$tariff->getNumber()] = $tariff;
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

	/**
	 * @param int $tariff
	 * @return Entity_Tariff
	 */
	public function getTariffById($tariff)
	{
		return if_isset($this->tariffs[$tariff], NULL);
	}

	/**
	 * @param string $number
	 * @return Entity_Tariff
	 */
	public function getTariffByNumber($number)
	{
		return if_isset($this->tariffs[$number], NULL);
	}

	/**
	 * @return Entity_Tariff[]
	 */
	public function getTariffs()
	{
		return array_unique($this->tariffs);
	}

}