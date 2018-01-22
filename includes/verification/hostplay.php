<?php

use App\PaymentModule;

$heart->register_payment_module("hostplay", "PaymentModuleHostplay");

class PaymentModuleHostplay extends PaymentModule implements IPayment_Sms
{
	const SERVICE_ID = "hostplay";

	/** @var  string */
	protected $userId;

	/** @var  string */
	protected $sms_code;

    public function __construct()
	{
		parent::__construct();

		$this->sms_code = $this->data['sms_text'];
		$this->userId = $this->data['user_id'];
		
		$this->rates_number = array(
			'0.34'  => '7055',
			'0.67'  => '7155',
			'1.35'  => '7255',
			'2.02'  => '7355',
			'2.7'  => '7455',
			'3.38'  => '7555',
			'4.05'  => '76660',
			'6.08'  => '7955',
			'6.76'  => '91055',
			'7.43'  => '91155',
			'9.47'  => '91455',
			'12.85' => '91955',
			'13.53' => '92055',
			'16.91' => '92555'
		);		
	}

	public function verify_sms($return_code, $number)
	{
		$response = curl_get_contents(
			'http://hostplay.pl/api/payment/api_code_verify.php' .
			'?payment=homepay_sms' .
			'&userid=' . urlencode($this->userId) .
			'&comment=SklepSMS' .
			'&code=' . urlencode($return_code)
		);

		$response = json_decode($response, true);
		$response_number = $this->rates_number[number_format(floatval($response['kwota']), 2)];
		
		if (strtoupper($response['status']) == 'OK') {
			if ($response_number == $number) {
				return IPayment_Sms::OK;
			}

			return array(
				'status' => IPayment_Sms::BAD_NUMBER,
				'tariff' => $this->getTariffByNumber($response_number)->getId()
			);
		}

		if (strtoupper($response['status']) == 'FAIL') {
			if(strtoupper($response['error']) == "BAD_CODE") {
				return IPayment_Sms::BAD_CODE;
			}
			
			if(strtoupper($response['error']) == "BAD_CODE[1]") {
				return IPayment_Sms::BAD_CODE;
			}		
			
			if(strtoupper($response['error']) == "BAD_AMOUNT") {
				return IPayment_Sms::BAD_NUMBER;
			}
			
			if(strtoupper($response['error']) == "BAD_AMOUNT2") {
				return IPayment_Sms::BAD_NUMBER;
			}
		}
		
		return IPayment_Sms::SERVER_ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}
