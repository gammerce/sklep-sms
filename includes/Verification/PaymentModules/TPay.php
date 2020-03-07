<?php
namespace App\Verification\PaymentModules;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\FinalizedPayment;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;

/**
 * Created by MilyGosc.
 * @see https://forum.sklep-sms.pl/showthread.php?tid=88
 */
class TPay extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "transferuj";

    /** @var UrlGenerator */
    private $url;

    /** @var string */
    private $accountId;

    /** @var string */
    private $key;

    public function __construct(
        Requester $requester,
        UrlGenerator $urlGenerator,
        PaymentPlatform $paymentPlatform
    ) {
        parent::__construct($requester, $paymentPlatform);

        $this->url = $urlGenerator;
        $this->key = $this->getData('key');
        $this->accountId = $this->getData('account_id');
    }

    public static function getDataFields()
    {
        return [new DataField("key"), new DataField("account_id")];
    }

    public static function getSmsNumbers()
    {
        return [];
    }

    public function prepareTransfer(Purchase $purchase, $dataFilename)
    {
        $cost = round($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) / 100, 2);

        return [
            'url' => 'https://secure.transferuj.pl',
            'method' => 'POST',
            'id' => $this->accountId,
            'kwota' => $cost,
            'opis' => $purchase->getDesc(),
            'crc' => $dataFilename,
            'md5sum' => md5($this->accountId . $cost . $dataFilename . $this->key),
            'imie' => $purchase->user->getForename(),
            'nazwisko' => $purchase->user->getSurname(),
            'email' => $purchase->getEmail(),
            'pow_url' => $this->url->to("/page/tpay_success"),
            'pow_url_blad' => $this->url->to("/page/payment_error"),
            'wyn_url' => $this->url->to("/transfer/{$this->paymentPlatform->getId()}"),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $finalizedPayment = new FinalizedPayment();

        if ($this->isPaymentValid($body)) {
            $finalizedPayment->setStatus(true);
        }

        $finalizedPayment->setOrderId(array_get($body, 'tr_id'));
        $finalizedPayment->setAmount(array_get($body, 'tr_amount'));
        $finalizedPayment->setDataFilename(array_get($body, 'tr_crc'));
        $finalizedPayment->setExternalServiceId(array_get($body, 'id'));
        $finalizedPayment->setTestMode(array_get($body, 'test_mode', false));
        $finalizedPayment->setOutput('TRUE');

        return $finalizedPayment;
    }

    private function isPaymentValid($response)
    {
        if (empty($response)) {
            return false;
        }

        $isMd5Valid = $this->isMd5Valid(
            array_get($response, 'md5sum'),
            number_format(array_get($response, 'tr_amount'), 2, '.', ''),
            array_get($response, 'tr_crc'),
            array_get($response, 'tr_id')
        );

        if (!$isMd5Valid) {
            return false;
        }

        return array_get($response, 'tr_status') == 'TRUE' &&
            array_get($response, 'tr_error') == 'none';
    }

    private function isMd5Valid($md5sum, $transactionAmount, $crc, $transactionId)
    {
        if (!is_string($md5sum) || strlen($md5sum) !== 32) {
            return false;
        }

        $sign = md5($this->accountId . $transactionId . $transactionAmount . $crc . $this->key);

        return $md5sum === $sign;
    }
}
