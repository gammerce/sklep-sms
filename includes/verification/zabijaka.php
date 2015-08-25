<?php

$heart->register_payment_api("zabijaka", "PaymentModuleZabijaka");

class PaymentModuleZabijaka extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "zabijaka";

	public function verify_sms($sms_code, $sms_number)
	{

		$xml = simplexml_load_file("http://api.zabijaka.pl/1.1/" . urlencode($this->data['api']) . "/sms/" .
			get_sms_cost($sms_number) . "/" . urlencode($sms_code) . "/sms.xml/add");

		if ($xml) {
			if ($xml->error == '2') $output['status'] = "BAD_CODE";
			else if ($xml->error == '1') $output['status'] = "BAD_API";
			else if ($xml->success == '1') $output['status'] = "OK";
			else $output['status'] = "ERROR";
		} else
			$output['status'] = "NO_CONNECTION";

		return $output;
	}

}