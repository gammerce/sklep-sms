<?php

$heart->register_payment_module("1s1k", "PaymentModule_1s1k");

class PaymentModule_1s1k extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "1s1k";

	/** @var string */
	private $api;

	/** @var string */
	private $sms_code;

	private $rates = array();

	function __construct()
	{
		parent::__construct();

		$this->api = $this->data['api'];
		$this->sms_code = $this->data['sms_text'];

		$this->rates = array(
			'0.65' => '7169',
			'1.30' => '72550',
			'1.95' => '73550',
			'2.60' => '74550',
			'3.25' => '75550',
			'3.90' => '76550',
			'5.85' => '79550',
			'12.35' => '91986',
			'16.25' => '92596'
		);
	}

	public function verify_sms($return_code, $number)
	{
		$content = curl_get_contents(
			'http://www.1shot1kill.pl/api' .
			'?type=sms' .
			'&key=' . urlencode($this->api) .
			'&sms_code=' . urlencode($return_code) .
			'&comment='
		);

		if ($content === FALSE) {
			return IPayment_Sms::NO_CONNECTION;
		}

		$response = json_decode($content, true);
		if (!is_array($response)) {
			return IPayment_Sms::BAD_API;
		}

		$response_number = $this->rates[number_format(floatval($response['amount']), 2)];

		switch ($response['status']) {
			case 'ok':
				if ($response_number == $number) {
					return IPayment_Sms::OK;
				}

				return array(
					'status' => IPayment_Sms::BAD_NUMBER,
					'number' => $response_number
				);

			case 'fail':
				return IPayment_Sms::BAD_CODE;

			case 'error':
				switch ($response['desc']) {
					case 'internal api error':
						return IPayment_Sms::SERVER_ERROR;

					case 'wrong api type':
					case 'wrong api key':
						return IPayment_Sms::BAD_API;
				}

				return array(
					'status' => IPayment_Sms::UNKNOWN,
					'text' => $response['desc']
				);
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}
}