<?php

$heart->register_payment_api("cssetti", "PaymentModuleCssetti");

class PaymentModuleCssetti extends PaymentModule implements IPaymentSMS
{

    const SERVICE_ID = "cssetti";

    public function verify_sms($sms_code, $sms_number)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'kod' => $sms_code,
            'id' => $this->data['account_id']
        ));
        curl_setopt($ch, CURLOPT_URL, "http://www.cssetti.pl/api.php");
        $shop = curl_exec($ch);
        $shop = explode('|', $shop);
        curl_close($ch);

        if ($shop[0]) {
            $shop[1] = floatval($shop[1]) * 2;
            if ($shop[0] == '2') $output['status'] = "BAD_CODE"; // Bledny kod
            else if ($shop[0] == '3') $output['status'] = "BAD_CODE"; // Juz uzyty
            else if ($shop[0] == '4') $output['status'] = "SERVER_ERROR";
            else if ($shop[1] != floatval(get_sms_cost($sms_number))) {
                $output['status'] = "BAD_NUMBER";
                // Szukamy smsa z kwota rowna $shop[1]
                foreach ($this->smses as $sms) {
                    if (floatval(get_sms_cost($sms['number'])) == $shop[1]) {
                        $output['tariff'] = $sms['tariff'];
                        break;
                    }
                }
            } else if ($shop[0] == '1') $output['status'] = "OK";
            else $output['status'] = "ERROR";
        } else
            $output['status'] = "NO_CONNECTION";

        return $output;
    }

}
