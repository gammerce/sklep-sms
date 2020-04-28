<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
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
            'wyn_url' => $this->url->to("/api/ipn/transfer/{$this->paymentPlatform->getId()}"),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        // e.g. "40.80"
        $amount = price_to_int(array_get($body, 'tr_amount'));

        $finalizedPayment = new FinalizedPayment();
        $finalizedPayment->setStatus($this->isPaymentValid($body));
        $finalizedPayment->setOrderId(array_get($body, 'tr_id'));
        $finalizedPayment->setCost($amount);
        $finalizedPayment->setIncome($amount);
        $finalizedPayment->setDataFilename(array_get($body, 'tr_crc'));
        $finalizedPayment->setExternalServiceId(array_get($body, 'id'));
        $finalizedPayment->setTestMode(array_get($body, 'test_mode', false));
        $finalizedPayment->setOutput("TRUE");

        return $finalizedPayment;
    }

    private function isPaymentValid(array $body)
    {
        $isMd5Valid = $this->isMd5Valid(
            array_get($body, 'md5sum'),
            number_format(array_get($body, 'tr_amount'), 2, '.', ''),
            array_get($body, 'tr_crc'),
            array_get($body, 'tr_id')
        );

        return $isMd5Valid &&
            array_get($body, 'tr_status') === "TRUE" &&
            array_get($body, 'tr_error') === "none";
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
