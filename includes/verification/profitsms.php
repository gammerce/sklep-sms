<?php

$heart->register_payment_module("profitsms", "PaymentModule_Profitsms");

class PaymentModule_Profitsms extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "profitsms";

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
		$response = curl_get_contents(
			'http://profitsms.pl/check.php' .
			'?apiKey=' . urlencode($this->api) .
			'&code=' . urlencode($return_code) .
			'&smsNr=' . urlencode($number)
		);

		if ($response === false) {
			return IPayment_Sms::NO_CONNECTION;
		}

		$raport = explode('|', $response);
		switch ($raport['0']) {
			case 1:
				return IPayment_Sms::OK;

			case 0:
				return IPayment_Sms::BAD_CODE;
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}