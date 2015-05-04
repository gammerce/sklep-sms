<?php

$heart->register_payment_api("homepay", "PaymentModuleHomepay");

class PaymentModuleHomepay extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "homepay";

	public function verify_sms($sms_code, $sms_number)
	{
		$handle = fopen("http://homepay.pl/API/check_code.php?usr_id=" . urlencode($this->data['api']) .
			"&acc_id={$this->data[$sms_number]}&code=" . urlencode($sms_code), 'r');

		if ($handle) {
			$status = fgets($handle, 8);
			fclose($handle);

			if ($status == '0')
				$output['status'] = "BAD_CODE";
			else if ($status == '1')
				$output['status'] = "OK";
			else
				$output['status'] = "SERVER_ERROR";
		} else
			$output['status'] = "NO_CONNECTION";

		return $output;
	}

}