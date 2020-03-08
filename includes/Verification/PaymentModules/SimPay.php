<?php
namespace App\Verification\PaymentModules;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Support\Result;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

// TODO Display information about price and charge amount

// https://docs.simpay.pl/#wstep
class SimPay extends PaymentModule implements SupportSms, SupportDirectBilling
{
    const MODULE_ID = "simpay";

    /** @var UrlGenerator */
    private $url;

    /** @var Translator */
    private $lang;

    public function __construct(
        Requester $requester,
        UrlGenerator $url,
        TranslationManager $translationManager,
        PaymentPlatform $paymentPlatform
    ) {
        parent::__construct($requester, $paymentPlatform);
        $this->url = $url;
        $this->lang = $translationManager->user();
    }

    public static function getDataFields()
    {
        return [
            new DataField("key"),
            new DataField("secret"),
            new DataField("service_id", "SMS Service ID"),
            new DataField("sms_text"),
            //            new DataField("direct_billing_service_id", "Direct Billing Service ID"),
            //            new DataField("direct_billing_api_key", "Direct Billing API Key"),
        ];
    }

    public static function getSmsNumbers()
    {
        return [
            new SmsNumber("7055"),
            new SmsNumber("7136"),
            new SmsNumber("7255"),
            new SmsNumber("7355"),
            new SmsNumber("7455"),
            new SmsNumber("7555"),
            new SmsNumber("7636"),
            new SmsNumber("77464"),
            new SmsNumber("78464"),
            new SmsNumber("7936"),
            new SmsNumber("91055"),
            new SmsNumber("91155"),
            new SmsNumber("91455"),
            new SmsNumber("91664"),
            new SmsNumber("91955"),
            new SmsNumber("92055"),
            new SmsNumber("92555"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->post('https://simpay.pl/api/1/status', [
            'params' => [
                'auth' => [
                    'key' => $this->getKey(),
                    'secret' => $this->getSecret(),
                ],
                'service_id' => $this->getServiceId(),
                'number' => $number,
                'code' => $returnCode,
            ],
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();

        if (isset($content['respond']['status']) && $content['respond']['status'] == 'OK') {
            return new SmsSuccessResult(!!$content['respond']['test']);
        }

        if (isset($content['error'][0]) && is_array($content['error'][0])) {
            switch ((int) $content['error'][0]['error_code']) {
                case 103:
                case 104:
                    throw new WrongCredentialsException();

                case 404:
                case 405:
                    throw new BadCodeException();
            }

            throw new ExternalErrorException($content['error'][0]['error_name']);
        }

        throw new UnknownErrorException();
    }

    public function prepareDirectBilling(Purchase $purchase, $dataFilename)
    {
        $amount = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
        $serviceId = $this->getDirectBillingServiceId();
        $control = $dataFilename;
        $apiKey = $this->getDirectBillingApiKey();

        $response = $this->requester->post("https://simpay.pl/db/api", [
            'serviceId' => $serviceId,
            'control' => $control,
            'complete' => $this->url->to("/page/payment_success"),
            'failure' => $this->url->to("/page/payment_error"),
            'amount_gross' => $amount,
            'sign' => hash('sha256', $serviceId . $amount . $control . $apiKey),
        ]);

        $result = $response->json();
        $status = array_get($result, "status");
        $message = array_get($result, "message");

        if ($status === "success") {
            return new Result("external", $this->lang->t("external_payment_prepared"), [
                "data" => [
                    "url" => $result["link"],
                ],
            ]);
        }

        return new Result("error", "SimPay response. $status: $message", false);
    }

    public function getSmsCode()
    {
        return $this->getData('sms_text');
    }

    private function getKey()
    {
        return $this->getData('key');
    }

    private function getSecret()
    {
        return $this->getData('secret');
    }

    private function getServiceId()
    {
        return $this->getData('service_id');
    }

    private function getDirectBillingServiceId()
    {
        return $this->getData('direct_billing_service_id');
    }

    private function getDirectBillingApiKey()
    {
        return $this->getData('direct_billing_api_key');
    }

    public function finalizeDirectBilling(array $query, array $body)
    {
        // TODO: Implement finalizeDirectBilling() method.
    }
}
