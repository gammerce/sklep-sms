<?php

$heart->register_payment_module("mintshost", "PaymentModule_Mintshost");

class PaymentModule_Mintshost extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "mintshost";

	/** @var  string */
	private $email;

	/** @var  string */
	private $sms_code;

	function __construct()
	{
		parent::__construct();

		$this->sms_code = $this->data['sms_text'];
		$this->email = $this->data['email'];
	}

	public function verify_sms($return_code, $number)
	{
		$status = curl_get_contents(
			'http://mintshost.pl/sms2.php' .
			'?kod=' . urlencode($return_code) .
			'&sms=' . urlencode($number) .
			'&email=' . urlencode($this->email)
		);

		if ($status === false) {
			return IPayment_Sms::NO_CONNECTION;
		}

		if ($status == "0") {
			return IPayment_Sms::BAD_CODE;
		}

		if ($status == "1") {
			return IPayment_Sms::OK;
		}

		if ($status == "2") {
			return IPayment_Sms::BAD_EMAIL;
		}

		if ($status == "3") {
			return IPayment_Sms::BAD_DATA;
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}