<?php

$heart->register_payment_module("zabijaka", "PaymentModule_Zabijaka");

class PaymentModule_Zabijaka extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "zabijaka";

	/** @var  string */
	private $api;

	/** @var  string */
	private $sms_code;

	function __construct()
	{
		parent::__construct();

		$this->api = $this->data['api'];
		$this->sms_code = $this->data['sms_text'];
	}

	public function verify_sms($return_code, $number)
	{
		$xml = simplexml_load_file(
			'http://api.zabijaka.pl/1.1' .
			'/' . urlencode($this->api) .
			'/sms' .
			'/' . round(get_sms_cost($number) / 100) .
			'/' . urlencode($return_code) .
			'/sms.xml/add'
		);

		echo 'http://api.zabijaka.pl/1.1' .
			'/' . urlencode($this->api) .
			'/sms' .
			'/' . round(get_sms_cost($number) / 100) .
			'/' . urlencode($return_code) .
			'/sms.xml/add';

		var_dump($xml);

		if (!$xml) {
			return IPayment_Sms::NO_CONNECTION;
		}

		if ($xml->error == '2') {
			return IPayment_Sms::BAD_CODE;
		}

		if ($xml->error == '1') {
			return IPayment_Sms::BAD_API;
		}

		if ($xml->success == '1') {
			return IPayment_Sms::OK;
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}