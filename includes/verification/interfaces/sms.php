<?php

interface IPayment_Sms
{

	const OK = 11;
	const BAD_CODE = 12;
	const BAD_NUMBER = 13;
	const BAD_API = 14;
	const BAD_EMAIL = 15;
	const BAD_DATA = 16;
	const SERVER_ERROR = 17;
	const MISCONFIGURATION = 18;
	const ERROR = 19;
	const NO_CONNECTION = 20;
	const UNKNOWN = 21;
	
	/**
	 * Weryfikacja kodu zwrotnego otrzymanego poprzez wyslanie SMSa na dany numer
	 *
	 * @param string $return_code kod zwrotny
	 * @param string $number numer na który powinien był zostać wysłany SMS
	 * @return int | array
	 *  status => zwracany status sms
	 *  number => numer na który został wysłany SMS
	 */
	public function verify_sms($return_code, $number);

	/**
	 * Zwraca kod sms, który należy wpisać w wiadomości sms
	 *
	 * @return string
	 */
	public function getSmsCode();

}