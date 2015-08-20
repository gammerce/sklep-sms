<?php

$heart->register_payment_api("cssetti", "PaymentModuleCssetti");

class PaymentModuleCssetti extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "cssetti";

	/** @var array */
	private $numbers = array();

	function __construct()
	{
		parent::__construct();

		$data = json_decode(file_get_contents('http://cssetti.pl/Api/SmsApiV2GetData.php'), true);

		// Pozyskujemy kod ktory nalezy wpisac jako tresc SMSa
		$this->data['sms_text'] = $data['Code'];

		foreach ($data['Numbers'] as $number_data) {
			$this->numbers[strval($number_data['TopUpAmount'])] = $number_data['Number'];
		}
	}

	public function verify_sms($sms_code, $sms_number)
	{
		$content = curl_get_contents(
			'http://cssetti.pl/Api/SmsApiV2CheckCode.php?UserId=' . urlencode($this->data['account_id']) . '&Code=' .  urlencode($sms_code)
		);

		if ($content === false) {
			return array(
				'status' => 'NO_CONNECTION'
			);
		}

		if (!is_numeric($content)) {
			return array(
				'status' => 'ERROR'
			);
		}

		if ($content == 0) {
			return array(
				'status' => 'BAD_CODE'
			);
		}

		if ($content == -1) {
			return array(
				'status' => 'BAD_API'
			);
		}

		if ($content == -2 || $content == -3) {
			return array(
				'status' => 'SERVER_ERROR'
			);
		}

		if ($content > 0) {
			if (!isset($this->numbers[strval($content)]) || $this->numbers[strval($content)] != $sms_number)
				return array(
					'status' => 'BAD_NUMBER',
					'tariff' => $this->smses[$this->numbers[strval($content)]]['tariff']
				);

			return array(
				'status' => 'OK'
			);
		}

		return array(
			'status' => 'ERROR'
		);
	}

}
