<?php

$heart->register_payment_api("mintshost", "PaymentModuleMintshost");

class PaymentModuleMintshost extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "mintshost";

	public function verify_sms($sms_code, $sms_number)
	{
		$status = curl_get_contents("http://mintshost.pl/sms.php?kod=" . urlencode($sms_code) . "&sms=" . urlencode($sms_number) . "&email=" . urlencode($this->data['email']));

		if ($status == "0") {
			$output['status'] = "BAD_CODE";
		} else if ($status == "1") {
			$output['status'] = "OK";
		} else if ($status == "2") {
			$output['status'] = "BAD_EMAIL";
		} else if ($status == "3") {
			$output['status'] = "BAD_DATA";
		} else
			$output['status'] = "ERROR";

		return $output;
	}

}