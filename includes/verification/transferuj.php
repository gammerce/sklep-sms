<?php

/**
 * Created by MilyGosc.
 * URL: https://forum.sklep-sms.pl/showthread.php?tid=88
 */

use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\PaymentModule;
use App\Settings;

class PaymentModuleTransferuj extends PaymentModule implements IPayment_Transfer
{
    const SERVICE_ID = "transferuj";

    /** @var string */
    private $account_id;

    /** @var string */
    private $key;

    /** @var Settings */
    private $settings;

    public function __construct()
    {
        parent::__construct();

        $this->settings = app()->make(Settings::class);
        $this->key = $this->data['key'];
        $this->account_id = $this->data['account_id'];
    }

    /**
     * @param Purchase $purchase_data
     * @param string   $data_filename
     * @return array
     */
    public function prepare_transfer($purchase_data, $data_filename)
    {
        // Zamieniamy grosze na złotówki
        $cost = round($purchase_data->getPayment('cost') / 100, 2);

        return [
            'url'          => 'https://secure.transferuj.pl',
            'method'       => 'POST',
            'id'           => $this->account_id,
            'kwota'        => $cost,
            'opis'         => $purchase_data->getDesc(),
            'crc'          => $data_filename,
            'md5sum'       => md5($this->account_id . $cost . $data_filename . $this->key),
            'imie'         => $purchase_data->user->getForename(false),
            'nazwisko'     => $purchase_data->user->getSurname(false),
            'email'        => $purchase_data->getEmail(),
            'pow_url'      => $this->settings['shop_url_slash'] . "/page/transferuj_ok",
            'pow_url_blad' => $this->settings['shop_url_slash'] . "/page/transferuj_bad",
            'wyn_url'      => $this->settings['shop_url_slash'] . "/transfer/transferuj",
        ];
    }

    public function finalizeTransfer($get, $post)
    {
        $transfer_finalize = new TransferFinalize();

        if ($this->isPaymentValid($post)) {
            $transfer_finalize->setStatus(true);
        }

        $transfer_finalize->setOrderid($post['tr_id']);
        $transfer_finalize->setAmount($post['tr_amount']);
        $transfer_finalize->setDataFilename($post['tr_crc']);
        $transfer_finalize->setTransferService($post['id']);
        $transfer_finalize->setOutput('TRUE');

        return $transfer_finalize;
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

        return $md5sum === md5($this->account_id . $transactionId . $transactionAmount . $crc . $this->key);
    }
}
