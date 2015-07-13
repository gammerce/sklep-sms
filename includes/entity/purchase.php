<?php

class Entity_Purchase {

	/**
	 * @var string
	 */
	private $service = NULL;

	/**
	 * @var array
	 */
	private $order = NULL;

	/**
	 * @var array
	 */
	private $user = NULL;

	/**
	 * @var integer
	 */
	private $tariff = NULL;

	/**
	 * @var string
	 */
	private $email = NULL;

	/**
	 * @var array
	 */
	private $payment = NULL;

	function __construct($data) {
		global $user;
		$this->user = $user;

		if (isset($data['service']))
			$this->setService($data['service']);

		if (isset($data['user']))
			$this->setUser($data['user']);

		if (isset($data['order']))
			$this->setOrder($data['order']);

		if (isset($data['tariff']))
			$this->setTariff($data['tariff']);

		if (isset($data['email']))
			$this->setEmail($data['email']);

		if (isset($data['payment']))
			$this->setPayment($data['payment']);
	}

	public function setService($service) {
		$this->service = (string)$service;
	}

	public function setOrder($order) {
		foreach($order as $key => $value)
			$this->order[$key] = $value;
	}

	public function setUser($user) {
		foreach($user as $key => $value)
			$this->user[$key] = $value;
	}

	public function setTariff($tariff) {
		$this->tariff = (integer)$tariff;
	}

	public function setEmail($email) {
		$this->email = (string)$email;
	}

	public function setPayment($payment) {
		foreach($payment as $key => $value)
			$this->payment[$key] = $value;
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
	 * @param string $key
	 * @return mixed
	 */
	public function getUser($key = NULL) {
		if ($key === NULL)
			return $this->user;

		return if_isset($this->user[$key], NULL);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getPayment($key) {
		return if_isset($this->payment[$key], NULL);
	}

	public function getTariff() {
		return $this->tariff;
	}

	public function getEmail() {
		return $this->email;
	}

}