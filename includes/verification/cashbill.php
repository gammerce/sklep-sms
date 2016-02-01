<?php

$heart->register_payment_module("cashbill", "PaymentModule_Cashbill");

class PaymentModule_Cashbill extends PaymentModule implements IPayment_Sms, IPayment_Transfer
{

	const SERVICE_ID = "cashbill";

	/** @var  string */
	private $key;

	/** @var  string */
	private $service;

	/** @var  string */
	private $sms_code;

	function __construct()
	{
		parent::__construct();

		$this->service = $this->data['service'];
		$this->key = $this->data['key'];
		$this->sms_code = $this->data['sms_text'];
	}

	public function verify_sms($return_code, $number)
	{
		$handle = fopen(
			'http://sms.cashbill.pl/backcode_check_singleuse_noip.php' .
			'?id=' .
			'&code=' . urlencode($this->sms_code) .
			'&check=' . urlencode($return_code),
			'r'
		);

		if ($handle) {
			$status = fgets($handle, 8);
			/*$czas_zycia = */
			fgets($handle, 24);
			/*$foo = */
			fgets($handle, 96);
			$bramka = fgets($handle, 96);
			fclose($handle);

			if ($status == '0') {
				return IPayment_Sms::BAD_CODE;
			}

			if ($number !== $bramka) {
				return array(
					'status' => IPayment_Sms::BAD_NUMBER,
					'tariff' => $this->getTariffByNumber($bramka)->getId()
				);
			}

			return IPayment_Sms::OK;
		}

		return IPayment_Sms::NO_CONNECTION;
	}

	public function prepare_transfer($purchase_data, $data_filename)
	{
		// Zamieniamy grosze na złotówki
		$cost = round($purchase_data->getPayment('cost') / 100, 2);

		return array(
			'url'      => 'https://pay.cashbill.pl/form/pay.php',
			'service'  => $this->getService(),
			'desc'     => $purchase_data->getDesc(),
			'forname'  => $purchase_data->user->getForename(false),
			'surname'  => $purchase_data->user->getSurname(false),
			'email'    => $purchase_data->getEmail(),
			'amount'   => $cost,
			'userdata' => $data_filename,
			'sign'     => md5($this->getService() . $cost . $purchase_data->getDesc() . $data_filename . $purchase_data->user->getForename(false) .
				$purchase_data->user->getSurname(false) . $purchase_data->getEmail() . $this->getKey())
		);
	}

	public function finalizeTransfer($get, $post)
	{
		$transfer_finalize = new Entity_TransferFinalize();

		if ($this->check_sign($post, $this->getKey(), $post['sign']) && strtoupper($post['status']) == 'OK' && $post['service'] == $this->getService()) {
			$transfer_finalize->setStatus(true);
		}

		$transfer_finalize->setOrderid($post['orderid']);
		$transfer_finalize->setAmount($post['amount']);
		$transfer_finalize->setDataFilename($post['userdata']);
		$transfer_finalize->setTransferService($post['service']);
		$transfer_finalize->setOutput('OK');

		return $transfer_finalize;
	}

	/**
	 * Funkcja sprawdzajaca poprawnosc sygnatury
	 * przy płatnościach za pomocą przelewu
	 *
	 * @param $data - dane
	 * @param $key - klucz do hashowania
	 * @param $sign - hash danych
	 *
	 * @return bool
	 */
	public function check_sign($data, $key, $sign)
	{
		return md5($data['service'] . $data['orderid'] . $data['amount'] . $data['userdata'] . $data['status'] . $key) == $sign;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @return string
	 */
	public function getService()
	{
		return $this->service;
	}
}