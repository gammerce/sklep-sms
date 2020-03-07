<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Models\FinalizedPayment;
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
class Microsms extends PaymentModule implements SupportSms, SupportTransfer
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
                "Kod błędu: {$content['error']['errorCode']} - {$content['error']['message']}"
            );
            throw new UnknownErrorException();
        }

        if ($content['connect'] === false) {
            $errorCode = $content['data']['errorCode'];

            if ($errorCode == 1) {
                throw new BadCodeException();
            }

            $this->fileLogger->error("Kod błędu: $errorCode - {$content['data']['message']}");
            throw new UnknownErrorException();
        }

        if ($content['data']['status'] == 1) {
            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function prepareTransfer(Purchase $purchase, $dataFilename)
    {
        $cost = round($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) / 100, 2);
        $signature = hash('sha256', $this->shopId . $this->hash . $cost);

        return [
            'url' => 'https://microsms.pl/api/bankTransfer/',
            'method' => 'GET',
            'shopid' => $this->shopId,
            'signature' => $signature,
            'amount' => $cost,
            'control' => $dataFilename,
            'return_urlc' => $this->url->to("/transfer/{$this->paymentPlatform->getId()}"),
            'return_url' => $this->url->to("/page/payment_success"),
            'description' => $purchase->getDesc(),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $finalizedPayment = new FinalizedPayment();

        if ($this->isPaymentValid($body)) {
            $finalizedPayment->setStatus(true);
        }

        $isTest = strtolower(array_get($body, 'test')) === "true";

        $finalizedPayment->setOrderId(array_get($body, 'orderID'));
        $finalizedPayment->setAmount(array_get($body, 'amountPay'));
        $finalizedPayment->setDataFilename(array_get($body, 'control'));
        $finalizedPayment->setTestMode($isTest);
        $finalizedPayment->setOutput("OK");

        return $finalizedPayment;
    }

    private function isPaymentValid(array $body)
    {
        if (strtolower(array_get($body, 'status')) !== "true") {
            return false;
        }

        if (array_get($body, 'userid') != $this->userId) {
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
