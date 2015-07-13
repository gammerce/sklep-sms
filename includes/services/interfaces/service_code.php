<?php

interface IService_ServiceCode
{
	/**
	 * Sprawdza czy dany zakup może być zrealizowany ( opłacony ) przez dany kod na usługę
	 *
	 * @param array $purchase
	 * @param array $code
	 * @return bool
	 */
	public function service_code_validate($purchase, $code);
}