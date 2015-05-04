<?php

$heart->register_payment_api("1s1k", "PaymentModule1s1k");

class PaymentModule1s1k extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "1s1k";
	private $rates = array();

	function __construct()
	{
		parent::__construct(); // Wywolujemy konstruktor klasy którą rozszerzamy

		$this->rates = array(
			"0.65" => "7169",
			"1.30" => "72550",
			"1.95" => "73550",
			"2.60" => "74550",
			"3.25" => "75550",
			"3.90" => "76550",
			"5.85" => "79550",
			"12.35" => "91986",
			"16.25" => "92596"
		);
	}

	public function verify_sms($sms_code, $sms_number)
	{
		$content = curl_get_contents("http://www.1shot1kill.pl/api?type=sms&key=" . urlencode($this->data['api']) . "&sms_code=" . urlencode($sms_code) . "&comment=");
		$return = json_decode($content, true);

		if (!is_array($return)) {
			$output['status'] = "BAD_API";
		} else {
			$number = $this->rates[number_format(floatval($return['amount']), 2)];

			switch ($return['status']) {
				case "ok":
					if ($number == $sms_number) {
						$output['status'] = "OK";
					} else {
						$output['status'] = "BAD_NUMBER";
						$output['number'] = $number;
					}
					break;
				case "fail":
					$output['status'] = "BAD_CODE";
					break;
				case "error":
					switch ($return['desc']) {
						case "internal api error":
							$output['status'] = "SERVER_ERROR";
							break;
						case "wrong api type":
							$output['status'] = "BAD_API";
							break;
						case "wrong api key":
							$output['status'] = "BAD_API";
							break;
						default:
							$output['status'] = "ERROR";
					}
					break;
				default:
					$output['status'] = "ERROR";
			}
		}

		return $output;
	}

}