<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see https://microsms.pl/documents/dokumentacja_przelewy_microsms.pdf
 */
class MicroSMS extends PaymentModule implements SupportSms, SupportTransfer
{
    const MODULE_ID = "microsms";

    public static function getDataFields(): array
    {
        return [
            new DataField("api"),
            new DataField("sms_text"),
            new DataField("service_id"),
            new DataField("shop_id"),
            new DataField("hash"),
        ];
    }

    public function getSmsNumbers(): array
    {
        return [
            new SmsNumber("71480", 48),
            new SmsNumber("72480", 96),
            new SmsNumber("73480", 144),
            new SmsNumber("74480", 192),
            new SmsNumber("75480", 240),
            new SmsNumber("76480", 288),
            new SmsNumber("79480", 432),
            new SmsNumber("91400", 672),
            new SmsNumber("91900", 912),
            new SmsNumber("92022", 960),
            new SmsNumber("92521", 1200),
        ];
    }

    public function verifySms(string $returnCode, string $number): SmsSuccessResult
    {
        $response = $this->requester->get("https://microsms.pl/api/v2/index.php", [
            "userid" => $this->getUserId(),
            "number" => $number,
            "code" => $returnCode,
            "serviceid" => $this->getServiceId(),
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        if ($response->isBadResponse()) {
            throw new ServerErrorException();
        }

        $content = $response->json();

        if (strlen(array_get($content, "error"))) {
            $this->fileLogger->error(
                "MicroSMS sms. Error {$content["error"]["errorCode"]} - {$content["error"]["message"]}"
            );
            throw new UnknownErrorException();
        }

        if ($content["connect"] === false) {
            $errorCode = $content["data"]["errorCode"];

            if ($errorCode == 1) {
                throw new BadCodeException();
            }

            $this->fileLogger->error(
                "MicroSMS sms. DataError [$errorCode] - {$content["data"]["message"]}"
            );
            throw new UnknownErrorException();
        }

        if ($content["data"]["status"] == 1) {
            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function prepareTransfer(Money $price, Purchase $purchase): array
    {
        $control = $purchase->getId();
        $signature = hash("sha256", $this->getShopId() . $this->getHash() . $price->asPrice());

        return [
            "url" => "https://microsms.pl/api/bankTransfer/",
            "method" => "GET",
            "data" => [
                "shopid" => $this->getShopId(),
                "signature" => $signature,
                "amount" => $price->asPrice(),
                "control" => $control,
                "return_urlc" => $this->url->to(
                    "/api/ipn/transfer/{$this->paymentPlatform->getId()}"
                ),
                "return_url" => $this->url->to("/page/payment_success"),
                "description" => $purchase->getTransferDescription(),
            ],
        ];
    }

    public function finalizeTransfer(Request $request): FinalizedPayment
    {
        $isTest = strtolower($request->request->get("test")) === "true";
        $amount = Money::fromPrice($request->request->get("amountPay"));

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($request))
            ->setOrderId($request->request->get("orderID"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($request->request->get("control"))
            ->setTestMode($isTest)
            ->setOutput("OK");
    }

    private function isPaymentValid(Request $request): bool
    {
        $userId = $request->request->get("userid");
        $status = strtolower($request->request->get("status"));
        $ip = get_ip($request);

        if ($status !== "true") {
            $this->fileLogger->error("MicroSMS transfer. Invalid status [$status]");
            return false;
        }

        if ($userId != $this->getUserId()) {
            $this->fileLogger->error(
                "MicroSMS transfer. Invalid userId, expected [{$this->getUserId()}], actual [$userId]"
            );
            return false;
        }

        if (!$this->isIpValid($ip)) {
            $this->fileLogger->error("MicroSMS transfer. Invalid IP address [$ip]");
            return false;
        }

        return true;
    }

    private function isIpValid($ip): bool
    {
        $response = $this->requester->get("https://microsms.pl/psc/ips/");

        if (!$response || $response->isBadResponse()) {
            return false;
        }

        return in_array($ip, explode(",", $response->getBody()));
    }

    public function getSmsCode(): string
    {
        return (string) $this->getData("sms_text");
    }

    private function getUserId(): string
    {
        return (string) $this->getData("api");
    }

    private function getServiceId(): string
    {
        return (string) $this->getData("service_id");
    }

    private function getShopId(): string
    {
        return (string) $this->getData("shop_id");
    }

    private function getHash(): string
    {
        return (string) $this->getData("hash");
    }
}
