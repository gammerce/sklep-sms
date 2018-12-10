<?php

use App\PaymentModule;

class PaymentModule_Pukawka extends PaymentModule implements SupportSms
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

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->api,
            'type'   => 'sms',
            'code'   => $returnCode,
        ]);

        if (!$response) {
            return SupportSms::NO_CONNECTION;
        }

        $get = $response->json();

        if (!empty($get)) {
            if ($get['error']) {
                return [
                    'status' => SupportSms::UNKNOWN,
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
                        return SupportSms::OK;
                    }

                    $tariff = $this->getTariffByNumber($s['numer']);

                    return [
                        'status' => SupportSms::BAD_NUMBER,
                        'tariff' => !is_null($tariff) ? $tariff->getId() : null,
                    ];
                }

                return SupportSms::ERROR;
            }

            return SupportSms::BAD_CODE;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
