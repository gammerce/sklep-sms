<?php

use App\Models\TransferFinalize;
use App\PaymentModule;
use App\Settings;

// https://microsms.pl/documents/dokumentacja_przelewy_microsms.pdf
class PaymentModule_Microsms extends PaymentModule implements IPayment_Sms, IPayment_Transfer
{
    const SERVICE_ID = "microsms";

    /** @var Settings */
    private $settings;

    /** @var string */
    private $serviceId;

    /** @var string */
    private $smsCode;

    /** @var string */
    private $shopId;

    /** @var string */
    private $userId;

    /** @var string */
    private $hash;

    public function __construct(Settings $settings)
    {
        parent::__construct();

        $this->settings = $settings;

        $this->userId = $this->data['api'];
        $this->smsCode = $this->data['sms_text'];
        $this->serviceId = $this->data['service_id'];
        $this->shopId = $this->data['shop_id'];
        $this->hash = $this->data['hash'];
    }

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get("http://microsms.pl/api/v2/index.php", [
            "userid"    => $this->userId,
            "number"    => $number,
            "code"      => $return_code,
            "serviceid" => $this->serviceId,
        ]);

        if (!$response) {
            return IPayment_Sms::NO_CONNECTION;
        }

        if ($response->isBadResponse()) {
            return IPayment_Sms::BAD_API;
        }

        $content = $response->json();

        if (strlen(array_get($content, 'error'))) {
            log_error("Kod błędu: {$content['error']['errorCode']} - {$content['error']['message']}");
            return IPayment_Sms::ERROR;
        }

        if ($content['connect'] === false) {
            log_error("Kod błędu: {$content['data']['errorCode']} - {$content['data']['message']}");
            return IPayment_Sms::ERROR;
        }

        if ($content['data']['status'] == 1) {
            return IPayment_Sms::OK;
        }

        return IPayment_Sms::BAD_CODE;
    }

    /**
     * @param \App\Models\Purchase $purchase_data
     * @param string $data_filename
     * @return array
     */
    public function prepare_transfer($purchase_data, $data_filename)
    {
        $cost = round($purchase_data->getPayment('cost') / 100, 2);
        $signature = hash('sha256', $this->shopId . $this->hash . $cost);

        return [
            'url'         => 'https://microsms.pl/api/bankTransfer/',
            'method'      => 'GET',
            'shopid'      => $this->shopId,
            'signature'   => $signature,
            'amount'      => $cost,
            'control'     => $data_filename,
            'return_urlc' => $this->settings['shop_url_slash'] . 'transfer_finalize.php?service=microsms',
            'return_url'  => $this->settings['shop_url_slash'] . 'index.php?pid=microsms_ok',
            'description' => $purchase_data->getDesc(),
        ];
    }

    public function finalizeTransfer($get, $post)
    {
        $transferFinalize = new TransferFinalize();

        if ($this->isPaymentValid($post)) {
            $transferFinalize->setOutput('OK');
        }

        $transferFinalize->setOrderid($post['orderID']);
        $transferFinalize->setAmount($post['amountPay']);
        $transferFinalize->setDataFilename($post['control']);

        return $transferFinalize;
    }

    private function isPaymentValid(array $post)
    {
        if ($post['status'] != true) {
            return false;
        }

        if ($post['userid'] != $this->userId) {
            return false;
        }

        return $this->isIpValid(get_ip());
    }

    private function isIpValid($ip)
    {
        $response = $this->requester->get('https://microsms.pl/psc/ips/');

        if (!$response || $response->isBadResponse()) {
            return false;
        }

        return in_array($ip, explode(',', $response->getBody()));
    }

    public function getSmsCode()
    {
        return $this->smsCode;
    }
}