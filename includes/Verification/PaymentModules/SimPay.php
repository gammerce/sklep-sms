<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\CustomErrorException;
use App\Verification\Results\SmsSuccessResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * @link https://docs.simpay.pl/
 */
class SimPay extends PaymentModule implements SupportSms, SupportDirectBilling
{
    const MODULE_ID = "simpay";

    /** @var ?string[] */
    private ?array $allowedIps = null;

    /** @var ?SmsNumber[] */
    private ?array $smsNumbers = null;

    public static function getDataFields(): array
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

    public function getSmsNumbers(): array
    {
        if ($this->smsNumbers === null) {
            $response = $this->requester->get(
                "https://api.simpay.pl/sms/{$this->getServiceId()}/numbers",
                [],
                [
                    "X-SIM-KEY" => $this->getKey(),
                    "X-SIM-PASSWORD" => $this->getSecret(),
                ]
            );
            $content = $response?->json();

            if (array_get($content, "success") !== true) {
                $message = array_get($content, "message", "n/a");
                throw new CustomErrorException("getting SMS numbers failed: $message");
            }

            $this->smsNumbers = collect(array_get($content, "data", []))
                ->map(fn(array $smsNumberDetails) => new SmsNumber($smsNumberDetails["number"]))
                ->all();
        }

        return $this->smsNumbers;
    }

    public function verifySms(string $returnCode, string $number): SmsSuccessResult
    {
        $response = $this->requester->post(
            "https://api.simpay.pl/sms/{$this->getServiceId()}",
            json_encode([
                "code" => $returnCode,
                "number" => $number,
            ]),
            [
                "X-SIM-KEY" => $this->getKey(),
                "X-SIM-PASSWORD" => $this->getSecret(),
                "Content-Type" => "application/json",
            ]
        );

        $content = $response?->json();

        if (
            array_get($content, "success") === true &&
            array_dot_get($content, "data.used") !== true
        ) {
            return new SmsSuccessResult(!!$content["data"]["test"]);
        }

        if ($response->getStatusCode() === 404 && array_get($content, "success") !== true) {
            throw new BadCodeException();
        }

        throw new CustomErrorException(array_get($content, "message", "n/a"));
    }

    public function prepareDirectBilling(Money $price, Purchase $purchase): PaymentResult
    {
        $serviceId = $this->getDirectBillingServiceId();
        $control = $purchase->getId();
        $apiKey = $this->getDirectBillingApiKey();

        $body = [
            "serviceId" => intval($serviceId),
            "control" => $control,
            "complete" => $this->url->to("/page/payment_success"),
            "failure" => $this->url->to("/page/payment_error"),
            "amount_gross" => $price->asPrice(),
            "sign" => hash("sha256", $serviceId . $price->asPrice() . $control . $apiKey),
        ];

        $response = $this->requester->post("https://simpay.pl/db/api", $body);

        if (!$response) {
            throw new PaymentProcessingException("error", "SimPay connection error");
        }

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

    public function finalizeDirectBilling(Request $request): FinalizedPayment
    {
        $id = $request->request->get("id");
        $valueGross = Money::fromPrice($request->request->get("valuenet_gross"));
        $valuePartner = Money::fromPrice($request->request->get("valuepartner"));
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

    public function getSmsCode(): string
    {
        return (string) $this->getData("sms_text");
    }

    private function getKey(): string
    {
        return (string) $this->getData("key");
    }

    private function getSecret(): string
    {
        return (string) $this->getData("secret");
    }

    private function getServiceId(): ?int
    {
        return as_int($this->getData("service_id"));
    }

    private function getDirectBillingServiceId(): string
    {
        return (string) $this->getData("direct_billing_service_id");
    }

    private function getDirectBillingApiKey(): string
    {
        return (string) $this->getData("direct_billing_api_key");
    }

    /**
     * @return string[]
     */
    private function getAllowedIPs(): array
    {
        if ($this->allowedIps === null) {
            $response = $this->requester->get("https://simpay.pl/api/get_ip");

            if (!$response) {
                throw new PaymentProcessingException("error", "Could not get simpay IPs.");
            }

            $data = $response->json();
            $this->allowedIps = array_merge(["127.0.0.1"], $data["respond"]["ips"]);
        }

        return $this->allowedIps;
    }

    private function isPaymentValid(Request $request): bool
    {
        $status = $request->request->get("status");

        return in_array(get_ip($request), $this->getAllowedIPs(), true) &&
            $status === "ORDER_PAYED" &&
            $this->isSignValid($request);
    }

    private function isSignValid(Request $request): bool
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
