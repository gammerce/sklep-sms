<?php

use App\PaymentModule;

class PaymentModule_Pukawka extends PaymentModule implements IPayment_Sms
{
    const SERVICE_ID = "pukawka";

    /** @var string */
    private $api;

    /** @var string */
    private $sms_code;

    private $stawki = [];

    public function __construct()
    {
        parent::__construct();

        $this->api = $this->data['api'];
        $this->sms_code = $this->data['sms_text'];

        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->api,
            'type'   => 'sms_table',
        ]);
        $this->stawki = $response ? $response->json() : null;
    }

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->api,
            'type'   => 'sms',
            'code'   => $return_code,
        ]);

        if (!$response) {
            return IPayment_Sms::NO_CONNECTION;
        }

        $get = $response->json();

        if (!empty($get)) {
            if ($get['error']) {
                return [
                    'status' => IPayment_Sms::UNKNOWN,
                    'text'   => $get['error'],
                ];
            }

            if ($get['status'] == 'ok') {
                $kwota = str_replace(',', '.', $get['kwota']);
                foreach ($this->stawki as $s) {
                    if (str_replace(',', '.', $s['wartosc']) != $kwota) {
                        continue;
                    }

                    if ($s['numer'] == $number) {
                        return IPayment_Sms::OK;
                    }

                    $tariff = $this->getTariffByNumber($s['numer']);

                    return [
                        'status' => IPayment_Sms::BAD_NUMBER,
                        'tariff' => !is_null($tariff) ? $tariff->getId() : null,
                    ];
                }

                return IPayment_Sms::ERROR;
            }

            return IPayment_Sms::BAD_CODE;
        }

        return IPayment_Sms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
