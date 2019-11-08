<?php
namespace App\Verification;

use App\System\Database;
use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Requesting\Requester;
use App\Routes\UrlGenerator;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
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
    protected $id = "microsms";

    /** @var Settings */
    private $settings;

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

    public function __construct(
        Database $database,
        Requester $requester,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator,
        Settings $settings
    ) {
        parent::__construct($database, $requester, $translationManager);

        $this->settings = $settings;
        $this->url = $urlGenerator;

        $this->userId = $this->data['api'];
        $this->smsCode = $this->data['sms_text'];
        $this->serviceId = $this->data['service_id'];
        $this->shopId = $this->data['shop_id'];
        $this->hash = $this->data['hash'];
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
            log_error(
                "Kod błędu: {$content['error']['errorCode']} - {$content['error']['message']}"
            );
            throw new UnknownErrorException();
        }

        if ($content['connect'] === false) {
            $errorCode = $content['data']['errorCode'];

            if ($errorCode == 1) {
                throw new BadCodeException();
            }

            log_error("Kod błędu: $errorCode - {$content['data']['message']}");
            throw new UnknownErrorException();
        }

        if ($content['data']['status'] == 1) {
            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function prepareTransfer(Purchase $purchase, $dataFilename)
    {
        $cost = round($purchase->getPayment('cost') / 100, 2);
        $signature = hash('sha256', $this->shopId . $this->hash . $cost);

        return [
            'url' => 'https://microsms.pl/api/bankTransfer/',
            'method' => 'GET',
            'shopid' => $this->shopId,
            'signature' => $signature,
            'amount' => $cost,
            'control' => $dataFilename,
            'return_urlc' => $this->url->to('transfer/microsms'),
            'return_url' => $this->url->to('page/transferuj_ok'),
            'description' => $purchase->getDesc(),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $transferFinalize = new TransferFinalize();

        if ($this->isPaymentValid($body)) {
            $transferFinalize->setStatus(true);
        }

        $transferFinalize->setOrderid($body['orderID']);
        $transferFinalize->setAmount($body['amountPay']);
        $transferFinalize->setDataFilename($body['control']);
        $transferFinalize->setOutput('OK');

        return $transferFinalize;
    }

    private function isPaymentValid(array $body)
    {
        if ($body['status'] != true) {
            return false;
        }

        if ($body['userid'] != $this->userId) {
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
