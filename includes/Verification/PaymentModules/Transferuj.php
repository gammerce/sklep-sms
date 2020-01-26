<?php
namespace App\Verification\PaymentModules;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Support\Database;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;

/**
 * Created by MilyGosc.
 * @see https://forum.sklep-sms.pl/showthread.php?tid=88
 */
class Transferuj extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "transferuj";

    /** @var UrlGenerator */
    private $url;

    /** @var string */
    private $accountId;

    /** @var string */
    private $key;

    public function __construct(
        Database $database,
        Requester $requester,
        UrlGenerator $urlGenerator,
        PaymentPlatform $paymentPlatform
    ) {
        parent::__construct($database, $requester, $paymentPlatform);

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
        $cost = round($purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) / 100, 2);

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
            'pow_url' => $this->url->to("page/transferuj_ok"),
            'pow_url_blad' => $this->url->to("page/transferuj_bad"),
            'wyn_url' => $this->url->to("transfer/{$this->paymentPlatform->getId()}"),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $transferFinalize = new TransferFinalize();

        if ($this->isPaymentValid($body)) {
            $transferFinalize->setStatus(true);
        }

        $transferFinalize->setOrderId(array_get($body, 'tr_id'));
        $transferFinalize->setAmount(array_get($body, 'tr_amount'));
        $transferFinalize->setDataFilename(array_get($body, 'tr_crc'));
        $transferFinalize->setTransferService(array_get($body, 'id'));
        $transferFinalize->setTestMode(array_get($body, 'test_mode', false));
        $transferFinalize->setOutput('TRUE');

        return $transferFinalize;
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
