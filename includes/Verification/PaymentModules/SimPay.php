<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
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
use Symfony\Component\HttpFoundation\Request;

/**
 * @link https://docs.simpay.pl/
 */
class SimPay extends PaymentModule implements SupportSms, SupportDirectBilling
{
    const MODULE_ID = "simpay";

    /** @var string[] */
    private $allowedIps;

    public static function getDataFields()
    {
        return [
            new DataField("key"),
            new DataField("secret"),
            new DataField("service_id", "SMS Service ID"),
            new DataField("sms_text"),
            new DataField("direct_billing_service_id", "Direct Billing Service ID"),
            new DataField("direct_billing_api_key", "Direct Billing API Key"),
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
        $response = $this->requester->post(
            "https://simpay.pl/api/1/status",
            json_encode([
                "params" => [
                    "auth" => [
                        "key" => $this->getKey(),
                        "secret" => $this->getSecret(),
                    ],
                    "service_id" => $this->getServiceId(),
                    "number" => $number,
                    "code" => $returnCode,
                ],
            ])
        );

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();

        if (isset($content["respond"]["status"]) && $content["respond"]["status"] == "OK") {
            return new SmsSuccessResult(!!$content["respond"]["test"]);
        }

        if (isset($content["error"][0]) && is_array($content["error"][0])) {
            switch ((int) $content["error"][0]["error_code"]) {
                case 103:
                case 104:
                    throw new WrongCredentialsException();

                case 404:
                case 405:
                    throw new BadCodeException();
            }

            throw new ExternalErrorException($content["error"][0]["error_name"]);
        }

        throw new UnknownErrorException();
    }

    public function prepareDirectBilling($price, Purchase $purchase)
    {
        $price /= 100;
        $serviceId = $this->getDirectBillingServiceId();
        $control = $purchase->getId();
        $apiKey = $this->getDirectBillingApiKey();

        $body = [
            "serviceId" => intval($serviceId),
            "control" => $control,
            "complete" => $this->url->to("/page/payment_success"),
            "failure" => $this->url->to("/page/payment_error"),
            "amount_gross" => $price,
            "sign" => hash("sha256", $serviceId . $price . $control . $apiKey),
        ];

        $response = $this->requester->post("https://simpay.pl/db/api", $body);

        $result = $response->json();
        $status = array_get($result, "status");
        $message = array_get($result, "message");
        $link = array_get($result, "link");

        if ($status === "success") {
            return new PaymentResult(PaymentResultType::EXTERNAL(), [
                "method" => "GET",
                "url" => $link,
            ]);
        }

        throw new PaymentProcessingException("error", "SimPay response. $status: $message");
    }

    public function finalizeDirectBilling(Request $request)
    {
        $this->tryToFetchIps();

        $id = $request->request->get("id");
        $valueGross = price_to_int($request->request->get("valuenet_gross"));
        $valuePartner = price_to_int($request->request->get("valuepartner"));
        $control = $request->request->get("control");

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($request))
            ->setOrderId($id)
            ->setCost($valueGross)
            ->setIncome($valuePartner)
            ->setTransactionId($control)
            ->setTestMode(false)
            ->setOutput("OK");
    }

    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    private function getKey()
    {
        return $this->getData("key");
    }

    private function getSecret()
    {
        return $this->getData("secret");
    }

    private function getServiceId()
    {
        return $this->getData("service_id");
    }

    private function getDirectBillingServiceId()
    {
        return $this->getData("direct_billing_service_id");
    }

    private function getDirectBillingApiKey()
    {
        return $this->getData("direct_billing_api_key");
    }

    private function tryToFetchIps()
    {
        if ($this->allowedIps === null) {
            $this->fetchIps();
        }
    }

    private function fetchIps()
    {
        $response = $this->requester->get("https://simpay.pl/api/get_ip");

        if (!$response) {
            $this->fileLogger->error("Could not get simpay ips.");
            return;
        }

        $data = $response->json();
        $this->allowedIps = array_merge(["127.0.0.1"], $data["respond"]["ips"]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isPaymentValid(Request $request)
    {
        $status = $request->request->get("status");

        return in_array(get_ip(), $this->allowedIps, true) &&
            $status === "ORDER_PAYED" &&
            $this->isSignValid($request);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isSignValid(Request $request)
    {
        $sign = $request->request->get("sign");
        $id = $request->request->get("id");
        $status = $request->request->get("status");
        $valueNet = $request->request->get("valuenet");
        $valuePartner = $request->request->get("valuepartner");
        $control = $request->request->get("control");

        $calculatedSign = hash(
            "sha256",
            $id . $status . $valueNet . $valuePartner . $control . $this->getDirectBillingApiKey()
        );

        return $sign === $calculatedSign;
    }
}
