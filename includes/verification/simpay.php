<?php

$heart->register_payment_api("simpay", "PaymentModuleSimpay");

class PaymentModuleSimpay extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "simpay";

	public function verify_sms($sms_code, $sms_number)
	{
		$response = curl_get_contents('https://simpay.pl/api/1/status', 10, true, array(
			'auth' => array(
				'key' => $this->data['key'],
				'secret' => $this->data['secret']
			),
			'service_id' => $this->data['service_id'],
			'number' => $sms_number,
			'code' => $sms_code
		));

		if (!$response) {
			$output['status'] = 'NO_CONNECTION';
		}

		$response = json_decode($response, true);

		if (isset($response['respond']['status']) && $response['respond']['status'] == 'OK') {
			$output['status'] = 'OK';
			$output['free'] = $response['respond']['test'];
		} else if (isset($response['error'][0]) && is_array($response['error'][0])) {
			switch (intval($response['error'][0]['error_code'])) {
				case 103:case 104:
					$output['status'] = 'BAD_API';
					break;

				case 404:case 405:
					$output['status'] = 'BAD_CODE';
					break;

				default:
					$output['status'] = 'DUNNO';
					$output['text'] = $response['error'][0]['error_name'];
			}
		} else {
			$output['status'] = 'ERROR';
		}

		return $output;
	}

}