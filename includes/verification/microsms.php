<?php

$heart->register_payment_api("microsms", "PaymentModuleMicrosms");

class PaymentModuleMicrosms extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "microsms";

	public function verify_sms($sms_code, $sms_number)
	{
		$handle = fopen("http://microsms.pl/api/check.php?userid=" . urlencode($this->data['api']) . "&number=" . urlencode($sms_number) .
			"&code=" . urlencode($sms_code) . '&serviceid=' . urlencode($this->data[$sms_number]), 'r');

		if ($handle) {
			$check = fgetcsv($handle, 1024);
			fclose($handle);

			if ($check[0] != 'E') {
				if ($check[0] == 1) {
					$output['status'] = "OK";
				} else {
					$output['status'] = "BAD_CODE";
				}
			} else {
				$output['status'] = "SERVICE_ERROR";
			}
		}
		else
			$output['status'] = "NO_CONNECTION";

		return $output;
	}

}