<?php

interface IPayment_Sms
{

	/**
	 * Weryfikacja kodu zwrotnego otrzymanego poprzez wyslanie SMSa na dany numer
	 *
	 * @param string $sms_code kod do weryfikacji
	 * @param string $sms_number numer na który został wysłany SMS
	 * @return array
	 *  status => zwracany kod
	 *  number => numer na który został wysłany SMS
	 */
	public function verify_sms($sms_code, $sms_number);

}