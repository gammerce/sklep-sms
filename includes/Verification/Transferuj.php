<?php
namespace App\Verification;

use App\Database;
use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Requesting\Requester;
use App\Routes\UrlGenerator;
use App\Settings;
use App\TranslationManager;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;

/**
 * Created by MilyGosc.
 * @see https://forum.sklep-sms.pl/showthread.php?tid=88
 */
class Transferuj extends PaymentModule implements SupportTransfer
{
    protected $id = "transferuj";

    /** @var Settings */
    private $settings;

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
        TranslationManager $translationManager,
        Settings $settings
    ) {
        parent::__construct($database, $requester, $translationManager);

        $this->settings = $settings;
        $this->url = $urlGenerator;
        $this->key = $this->data['key'];
        $this->accountId = $this->data['account_id'];
    }

    public function prepareTransfer(Purchase $purchase, $dataFilename)
    {
        // Zamieniamy grosze na złotówki
        $cost = round($purchase->getPayment('cost') / 100, 2);

        return [
            'url' => 'https://secure.transferuj.pl',
            'method' => 'POST',
            'id' => $this->accountId,
            'kwota' => $cost,
            'opis' => $purchase->getDesc(),
            'crc' => $dataFilename,
            'md5sum' => md5($this->accountId . $cost . $dataFilename . $this->key),
            'imie' => $purchase->user->getForename(false),
            'nazwisko' => $purchase->user->getSurname(false),
            'email' => $purchase->getEmail(),
            'pow_url' => $this->url->to("page/transferuj_ok"),
            'pow_url_blad' => $this->url->to("page/transferuj_bad"),
            'wyn_url' => $this->url->to("transfer/transferuj"),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $transferFinalize = new TransferFinalize();

        if ($this->isPaymentValid($body)) {
            $transferFinalize->setStatus(true);
        }

        $transferFinalize->setOrderid($body['tr_id']);
        $transferFinalize->setAmount($body['tr_amount']);
        $transferFinalize->setDataFilename($body['tr_crc']);
        $transferFinalize->setTransferService($body['id']);
        $transferFinalize->setOutput('TRUE');

        return $transferFinalize;
    }

    public function isPaymentValid($response)
    {
        if (empty($response)) {
            return false;
        }

        $isMd5Valid = $this->isMd5Valid(
            $response['md5sum'],
            number_format($response['tr_amount'], 2, '.', ''),
            $response['tr_crc'],
            $response['tr_id']
        );

        if (!$isMd5Valid) {
            return false;
        }

        return $response['tr_status'] == 'TRUE' && $response['tr_error'] == 'none';
    }

    private function isMd5Valid($md5sum, $transactionAmount, $crc, $transactionId)
    {
        if (!is_string($md5sum) || strlen($md5sum) !== 32) {
            return false;
        }

        return $md5sum ===
            md5($this->accountId . $transactionId . $transactionAmount . $crc . $this->key);
    }
}
