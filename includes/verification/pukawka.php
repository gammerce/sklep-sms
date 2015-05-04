<?php

$heart->register_payment_api("pukawka", "PaymentModulePukawka");

class PaymentModulePukawka extends PaymentModule implements IPaymentSMS
{

	const SERVICE_ID = "pukawka";
	private $stawki = array();

	function __construct()
	{
		parent::__construct(); // Wywolujemy konstruktor klasy którą rozszerzamy

		$this->stawki = json_decode(curl_get_contents("https://admin.pukawka.pl/api/?keyapi=" . urlencode($this->data['api']) . "&type=sms_table"));
	}

	public function verify_sms($sms_code, $sms_number)
	{
		$get = curl_get_contents("https://admin.pukawka.pl/api/?keyapi=" . urlencode($this->data['api']) . "&type=sms&code=" . urlencode($sms_code));

		if ($get) {
			$get = json_decode($get);

			if (is_object($get)) {
				if ($get->error) {
					$output['text'] = $get->error;
				} else {
					if ($get->status == "ok") {
						$kwota = str_replace(",", ".", $get->kwota);
						foreach ($this->stawki as $s) {
							if (str_replace(",", ".", $s->wartosc) == $kwota) {
								if ($s->numer == $sms_number) {
									$output['status'] = "OK";
								} else {
									$output['status'] = "BAD_NUMBER";
									$output['tariff'] = $this->smses[$s->numer]['tariff'];
								}
								break;
							}
						}
						if (!isset($output['status'])) {
							$output['status'] = "ERROR";
						}
					} else {
						$output['status'] = "BAD_CODE";
					}
				}
			} else {
				$output['status'] = "ERROR";
			}
		} else {
			$output['status'] = "NO_CONNECTION";
		}

		return $output;
	}

}