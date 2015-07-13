<?php

class Entity_Purchase {

	/**
	 * @var string
	 */
	private $service;

	/**
	 * @var array
	 */
	private $order;

	/**
	 * @var array
	 */
	private $user;

	/**
	 * @var integer
	 */
	private $tariff;

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var array
	 */
	private $payment;

	function __construct($data) {
		if (isset($data['service']))
			$this->setService($data['service']);

		if (isset($data['user']))
			$this->setUser($data['user']);

		if (isset($data['order']))
			$this->setOrder($data['order']);

		if (isset($data['tariff']))
			$this->setTariff($data['tariff']);

		if (isset($data['email']))
			$this->setUser($data['email']);

		if (isset($data['payment']))
			$this->setUser($data['payment']);
	}

	public function setService($service) {
		$this->service = $service;
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
		$this->tariff = $tariff;
	}

	public function setEmail($email) {
		$this->email = $email;
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
	public function getOrder($key) {
		return $this->order[$key];
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getUser($key) {
		return $this->user[$key];
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function getPayment($key) {
		return $this->payment[$key];
	}

	public function getTariff() {
		return $this->tariff;
	}

	public function getEmail() {
		return $this->email;
	}

}