<?php

class Entity_Tariff
{
	/** @var  int */
	private $id;

	/** @var  string */
	private $number;

	/** @var  int */
	private $provision;

	public function __toString() // Potrzebne do funkcji array_unique
	{
		return $this->getId() . '|' . $this->getNumber() . '|' . $this->getProvision();
	}

	function __construct($id, $provision, $number = NULL)
	{
		$this->id = (int)$id;
		$this->provision = (int)$provision;
		$this->number = isset($number) ? (string)$number : NULL;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getProvision()
	{
		return $this->provision;
	}

	/**
	 * @return string|null
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * Zwraca koszt brutto sms
	 *
	 * @return float
	 */
	public function getSmsCostBrutto()
	{
		global $settings;

		return (float)number_format(get_sms_cost($this->getNumber()) * $settings['vat'] / 100.0, 2);
	}
}