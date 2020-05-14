<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

/**
 * @see https://microsms.pl/documents/dokumentacja_przelewy_microsms.pdf
 */
class MicroSMS extends PaymentModule implements SupportSms, SupportTransfer
{
    const MODULE_ID = "microsms";

    /** @var UrlGenerator */
    private $url;

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

    /** @var FileLogger */
    private $fileLogger;

    public function __construct(
        Requester $requester,
        UrlGenerator $urlGenerator,
        PaymentPlatform $paymentPlatform,
        FileLogger $fileLogger
    ) {
        parent::__construct($requester, $paymentPlatform);

        $this->url = $urlGenerator;

        $this->userId = $this->getData('api');
        $this->smsCode = $this->getData('sms_text');
        $this->serviceId = $this->getData('service_id');
        $this->shopId = $this->getData('shop_id');
        $this->hash = $this->getData('hash');
        $this->fileLogger = $fileLogger;
    }

    public static function getDataFields()
    {
        return [
            new DataField("api"),
            new DataField("sms_text"),
            new DataField("service_id"),
            new DataField("shop_id"),
            new DataField("hash"),
        ];
    }

    public static function getSmsNumbers()
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

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get("https://microsms.pl/api/v2/index.php", [
            "userid" => $this->userId,
            "number" => $number,
            "code" => $returnCode,
            "serviceid" => $this->serviceId,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        if ($response->isBadResponse()) {
            throw new ServerErrorException();
        }

        $content = $response->json();

        if (strlen(array_get($content, 'error'))) {
            $this->fileLogger->error(
                "MicroSMS sms. Error {$content['error']['errorCode']} - {$content['error']['message']}"
            );
            throw new UnknownErrorException();
        }

        if ($content['connect'] === false) {
            $errorCode = $content['data']['errorCode'];

            if ($errorCode == 1) {
                throw new BadCodeException();
            }

            $this->fileLogger->error(
                "MicroSMS sms. DataError [$errorCode] - {$content['data']['message']}"
            );
            throw new UnknownErrorException();
        }

        if ($content['data']['status'] == 1) {
            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        $price /= 100;
        $control = $purchase->getId();
        $signature = hash("sha256", $this->shopId . $this->hash . $price);

        return [
            "url" => "https://microsms.pl/api/bankTransfer/",
            "method" => "GET",
            "shopid" => $this->shopId,
            "signature" => $signature,
            "amount" => $price,
            "control" => $control,
            "return_urlc" => $this->url->to("/api/ipn/transfer/{$this->paymentPlatform->getId()}"),
            "return_url" => $this->url->to("/page/payment_success"),
            "description" => $purchase->getDesc(),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $isTest = strtolower(array_get($body, "test")) === "true";
        $amount = price_to_int(array_get($body, "amountPay"));

        $finalizedPayment = new FinalizedPayment();
        $finalizedPayment->setStatus($this->isPaymentValid($body));
        $finalizedPayment->setOrderId(array_get($body, "orderID"));
        $finalizedPayment->setCost($amount);
        $finalizedPayment->setIncome($amount);
        $finalizedPayment->setTransactionId(array_get($body, "control"));
        $finalizedPayment->setTestMode($isTest);
        $finalizedPayment->setOutput("OK");

        return $finalizedPayment;
    }

    private function isPaymentValid(array $body)
    {
        $userId = array_get($body, "userid");
        $status = strtolower(array_get($body, "status"));
        $ip = get_ip();

        if ($status !== "true") {
            $this->fileLogger->error("MicroSMS transfer. Invalid status [{$status}]");
            return false;
        }

        if ($userId != $this->userId) {
            $this->fileLogger->error(
                "MicroSMS transfer. Invalid userId, expected [{$this->userId}], actual [{$userId}]"
            );
            return false;
        }

        if (!$this->isIpValid($ip)) {
            $this->fileLogger->error("MicroSMS transfer. Invalid IP address [{$ip}]");
            return false;
        }

        return true;
    }

    private function isIpValid($ip)
    {
        $response = $this->requester->get("https://microsms.pl/psc/ips/");

        if (!$response || $response->isBadResponse()) {
            return false;
        }

        return in_array($ip, explode(",", $response->getBody()));
    }

    public function getSmsCode()
    {
        return $this->smsCode;
    }
}
