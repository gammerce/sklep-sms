<?php

/**
 * Obiekty tej klasy są używane podczas przeprowadzania zakupu
 *
 * Class Entity_Purchase
 */
class Entity_Purchase {

	/**
	 * @var string
	 */
	private $service = NULL;

	/**
	 * Szczegóły zamawianej usługi
	 *
	 * @var array
	 */
	private $order = NULL;

	/**
	 * @var Entity_User
	 */
	public $user;

	/**
	 * @var Entity_Tariff
	 */
	private $tariff = NULL;

	/**
	 * @var string
	 */
	private $email = NULL;

	/**
	 * Szczegóły płatności
	 *
	 * @var array
	 */
	private $payment = NULL;

	/**
	 * Opis zakupu ( przydaje się przy płatności przelewem )
	 *
	 * @var string
	 */
	private $desc = NULL;

	function __construct() {
		global $user;
		$this->user = $user;
	}

	public function setService($service) {
		$this->service = (string)$service;
	}

	public function setOrder($order) {
		foreach($order as $key => $value)
			$this->order[$key] = $value;
	}

	/**
	 * @param Entity_Tariff $tariff
	 */
	public function setTariff($tariff) {
		$this->tariff = $tariff;
	}

	public function setEmail($email) {
		$this->email = (string)$email;
	}

	public function setPayment($payment) {
		foreach($payment as $key => $value) {
			$this->payment[$key] = $value;
		}
	}

	public function getService() {
		return $this->service;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getOrder($key = NULL) {
		if ($key === NULL)
			return $this->order;

		return if_isset($this->order[$key], NULL);
	}

	/**
	 * @param string|null $key
	 * @return mixed
	 */
	public function getPayment($key = NULL) {
		if ($key === NULL)
			return $this->payment;

		return if_isset($this->payment[$key], NULL);
	}

	/**
	 * @return Entity_Tariff
	 */
	public function getTariff() {
		return $this->tariff;
	}

	/**
	 * @param bool $escaped
	 * @return string
	 */
	public function getEmail($escaped = false) {
		return $escaped ? htmlspecialchars($this->email) : $this->email;
	}

	/**
	 * @return string
	 */
	public function getDesc()
	{
		return $this->desc;
	}

	/**
	 * @param string $desc
	 */
	public function setDesc($desc)
	{
		$this->desc = $desc;
	}

}