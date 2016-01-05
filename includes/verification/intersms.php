<?php

//$heart->register_payment_module("intersms", "PaymentModuleIntersms");

class PaymentModuleIntersms extends PaymentModule implements IPayment_Sms
{

	const SERVICE_ID = "intersms";

	/** @var  string */
	protected $userId;

	/** @var  string */
	protected $clientKey;

	/** @var  string */
	protected $bkey;

	/** @var  string */
	protected $sms_code;

	function __construct()
	{
		parent::__construct();

		$this->sms_code = $this->data['sms_text'];
		$this->userId = $this->data['user_id'];
		$this->bkey = pack('H*', $this->data['client_key']);
		$explodedSmsCode = explode('.', $this->sms_code);
		$this->sufix = $explodedSmsCode[1];
	}

	public function verify_sms($return_code, $number)
	{
		$tablica = array();
		$tablica['code'] = $return_code;
		$tablica['id'] = $this->userId;
		$tablica['sufix'] = $this->sufix;

		$sms_server = 'https://intersms.pl/sms_check.php';

		# utworzenie uchwytu do sesji cURL
		$opch = curl_init();

		curl_setopt($opch, CURLOPT_URL, $sms_server);
		curl_setopt($opch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($opch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($opch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($opch, CURLOPT_TIMEOUT, 100);
		curl_setopt($opch, CURLOPT_POST, 1);
		curl_setopt($opch, CURLOPT_POSTFIELDS, $tablica);

		# wywołanie sesji cURL i wpisanie
		# odpowiedzi serwera Płatności do zmiennej $wynik
		$wynik = curl_exec($opch);

		# zamknięcie sesji cURL
		curl_close($opch);

		# zapisz wynik zapytania do tablicy $dane
		$dane = explode("\n", $wynik);

		$status = $dane[0]; # Status transakcji. 1 - OK, 0 - błąd
		$amount = $dane[1]; # Kwota w groszach za przesłanie SMS lub kod błędu jeśli wartość status jest równa 0
		$control = $dane[2]; # Podpis transakcji przekazany z serwera Płatności

		# oblicz podpis transakcji
		$control_test = md5($this->userId . $this->sufix . $return_code . $this->bkey);

		# Kody błędów jeśli wartość $status jest równa 0
		# 1 - kod już był sprawdzony przez formularz Partnera więc jest nieważny
		# 2 - kod jest niewłaściwy
		# 3 - SUFIKS nie należy do użytkownika
		# 4 - niewłaściwy tryb sprawdzania kodów (np. sprawdzany sufiks dotyczy listy generowanych kodów zamiast kodów automatycznych)


		if ($status == '1') {
			if ($control_test != $control) {
				return IPayment_Sms::MISCONFIGURATION;
			}

			// Check whether prices are almost equal
			if (abs(get_sms_cost($number) - $amount) < 10) {
				return IPayment_Sms::OK;
			}

			$tariff = $this->getTariffBySmsCostBrutto($amount * 2 / 100);
			return array(
					'status' => IPayment_Sms::BAD_NUMBER,
					'tariff' => !is_null($tariff) ? $tariff->getId() : NULL
			);
		}

		if ($status == '0') {
			return IPayment_Sms::BAD_CODE;
		}

		return IPayment_Sms::ERROR;
	}

	public function getSmsCode()
	{
		return $this->sms_code;
	}

}