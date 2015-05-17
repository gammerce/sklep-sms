<?php

$heart->register_payment_api("profitsms", "PaymentModuleProfitsms");

class PaymentModuleProfitsms extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "profitsms";

	public function verify_sms($sms_code, $sms_number)
	{
		$url = "http://profitsms.pl/check.php?apiKey=" . urlencode($this->data['api']) .
			"&code=" . urlencode($sms_code) . "&smsNr=" . urlencode($sms_number);

		if (in_array('curl', get_loaded_extensions())) {
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$status = curl_exec($curl);
			curl_close($curl);
		} else {
			$status = curl_get_contents($url);
		}
		$raport = explode('|', $status);
		switch ($raport['0']) {
			case 1:
				$output['status'] = "OK";
				break;
			case 0:
				$output['status'] = "BAD_CODE";
				break;
			default:
				$output['status'] = "ERROR";
				break;
		}

		return $output;
	}

}