<?php

interface IService_PurchaseOutside
{
	/**
	 * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
	 * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
	 *
	 * @param array $data user:
	 *                            uid - id uzytkownika wykonującego zakupy
	 *                            ip - ip użytkownika wykonującego zakupy
	 *                            email - email -||-
	 *                            platform - -||-
	 *                        transaction:
	 *                            method - sposób płatności
	 *                            service - serwis mający obsłużyć płatność
	 *                            [sms_code] - kod zwrotny sms
	 *                        order:
	 *                            ... - dane zamówienia
	 *                        tariff - koszt usługi ( taryfa )
	 *
	 * @return array        'status'    - id wiadomości,
	 *                        'text'        - treść wiadomości
	 *                        'positive'    - czy udało się przeprowadzić zakup czy nie
	 *                        'purchase_data'    - dane zakupu ktore beda potrzebne przy pozniejszej platnosci
	 *                            'order'
	 *                                ...
	 *                            'user',
	 *                                'uid',
	 *                                'email'
	 *                                ...
	 *                            'tariff',
	 *                            'cost_transfer'
	 *                            'no_sms'
	 *                            'no_transfer'
	 *                            'no_wallet'
	 */
	public function purchase_validate_data($data);
}