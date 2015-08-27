<?php

class Entity_Tariff
{
	/** @var  int */
	private $id;

	/** @var  string */
	private $number;

	/** @var  int */
	private $provision;

	/** @var  bool */
	private $predefined = false;

	public function __toString() // Potrzebne do funkcji array_unique
	{
		return $this->getId() . '|' . $this->getNumber() . '|' . $this->getProvision();
	}

	/**
	 * @param int $id
	 * @param int $provision
	 * @param bool $predefined
	 * @param string | null $number
	 */
	function __construct($id, $provision, $predefined, $number = NULL)
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
	 * @param string $number
	 */
	public function setNumber($number)
	{
		$this->number = (string)$number;
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

	/**
	 * @return boolean
	 */
	public function isPredefined()
	{
		return $this->predefined;
	}
}