<?php

use App\PaymentModule;

class PaymentModule_Cssetti extends PaymentModule implements SupportSms
{
    const SERVICE_ID = "cssetti";

    /** @var  string */
    private $account_id;

    /** @var  string */
    private $sms_code;

    /** @var array */
    private $numbers = [];

    public function __construct()
    {
        parent::__construct();

        $response = $this->requester->get('http://cssetti.pl/Api/SmsApiV2GetData.php');
        $data = $response ? $response->json() : null;

        // CSSetti dostarcza w feedzie kod sms
        $this->sms_code = $data['Code'];

        foreach ($data['Numbers'] as $number_data) {
            $this->numbers[strval(floatval($number_data['TopUpAmount']))] = strval($number_data['Number']);
        }

        $this->account_id = $this->data['account_id'];
    }

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get('http://cssetti.pl/Api/SmsApiV2CheckCode.php', [
            'UserId' => $this->account_id,
            'Code'   => $return_code,
        ]);

        if ($response === false) {
            return SupportSms::NO_CONNECTION;
        }

        $content = $response->getBody();

        if (!is_numeric($content)) {
            return SupportSms::ERROR;
        }

        $content = strval(floatval($content));

        if ($content == '0') {
            return SupportSms::BAD_CODE;
        }

        if ($content == '-1') {
            return SupportSms::BAD_API;
        }

        if ($content == '-2' || $content == '-3') {
            return SupportSms::SERVER_ERROR;
        }

        if (floatval($content) > 0) {
            $expectedNumber = array_get($this->numbers, $content);

            if ($expectedNumber === null || $expectedNumber != $number) {
                $tariff = $this->getTariffByNumber($expectedNumber);

                return [
                    'status' => SupportSms::BAD_NUMBER,
                    'tariff' => $tariff ? $tariff->getId() : null,
                ];
            }

            return SupportSms::OK;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
