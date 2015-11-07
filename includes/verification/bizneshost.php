<?php

$heart->register_payment_module("bizneshost", "PaymentModule_Bizneshost");

class PaymentModule_Bizneshost extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = 'bizneshost';

	/** @var  int */
	protected $uid;

	/** @var  string */
	protected $sms_code;

	function __construct()
	{
		parent::__construct();

		$this->sms_code = $this->data['sms_text'];
		$this->uid = $this->data['uid'];
	}

	public function verify_sms($return_code, $number)
	{
		$status = curl_get_contents(
			'http://biznes-host.pl/api/sprawdzkod_v2.php' .
			'?uid=' . urlencode($this->uid) .
			'&kod=' . urlencode($return_code)
		);

		if ($status === false) {
			return IPayment_Sms::NO_CONNECTION;
		}

		$status = explode(':', $status);

		// Bad code
		if ($status[0] == 'E') {
			return IPayment_Sms::BAD_CODE;
		}

		// Code is correct
		if ($status[0] == '1') {
			// Check whether prices are equal
			if (get_sms_cost($number) / 100 == floatval($status[2])) {
				return IPayment_Sms::OK;
			}

			$tariff = $this->getTariffBySmsCostBrutto($status[1]);
			return array(
				'status' => IPayment_Sms::BAD_NUMBER,
				'tariff' => !is_null($tariff) ? $tariff->getId() : NULL
			);
		}

		// Code used
		if ($status[0] == '2') {
			return IPayment_Sms::BAD_CODE;
		}

		// No code - $return_code is empty
		if ($status[0] == '-1') {
			return IPayment_Sms::BAD_CODE;
		}

		// No uid
		if ($status[0] == '-2') {
			return IPayment_Sms::BAD_DATA;
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}