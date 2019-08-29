<?php
namespace App\Verification;

use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Results\SmsSuccessResult;

class Cashbill extends PaymentModule implements SupportSms, SupportTransfer
{
    protected $id = "cashbill";

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            'http://sms.cashbill.pl/backcode_check_singleuse_noip.php' .
                '?id=' .
                '&code=' .
                urlencode($this->getSmsCode()) .
                '&check=' .
                urlencode($returnCode),
            'r'
        );

        if ($handle) {
            $status = fgets($handle, 8);
            /*$czas_zycia = */
            fgets($handle, 24);
            /*$foo = */
            fgets($handle, 96);
            $bramka = fgets($handle, 96);
            fclose($handle);

            if ($status == '0') {
                throw new BadCodeException();
            }

            if ($number !== $bramka) {
                throw new BadNumberException($this->getTariffByNumber($bramka)->getId());
            }

            return new SmsSuccessResult();
        }

        throw new NoConnectionException();
    }

    /**
     * @param Purchase $purchase
     * @param string $dataFilename
     * @return array
     */
    public function prepareTransfer(Purchase $purchase, $dataFilename)
    {
        // Zamieniamy grosze na złotówki
        $cost = round($purchase->getPayment('cost') / 100, 2);

        return [
            'url' => 'https://pay.cashbill.pl/form/pay.php',
            'method' => 'POST',
            'service' => $this->getService(),
            'desc' => $purchase->getDesc(),
            'forname' => $purchase->user->getForename(false),
            'surname' => $purchase->user->getSurname(false),
            'email' => $purchase->getEmail(),
            'amount' => $cost,
            'userdata' => $dataFilename,
            'sign' => md5(
                $this->getService() .
                    $cost .
                    $purchase->getDesc() .
                    $dataFilename .
                    $purchase->user->getForename(false) .
                    $purchase->user->getSurname(false) .
                    $purchase->getEmail() .
                    $this->getKey()
            ),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $transferFinalize = new TransferFinalize();

        if (
            $this->checkSign($body, $this->getKey(), $body['sign']) &&
            strtoupper($body['status']) == 'OK' &&
            $body['service'] == $this->getService()
        ) {
            $transferFinalize->setStatus(true);
        }

        $transferFinalize->setOrderid($body['orderid']);
        $transferFinalize->setAmount($body['amount']);
        $transferFinalize->setDataFilename($body['userdata']);
        $transferFinalize->setTransferService($body['service']);
        $transferFinalize->setOutput('OK');

        return $transferFinalize;
    }

    /**
     * Funkcja sprawdzajaca poprawnosc sygnatury
     * przy płatnościach za pomocą przelewu
     *
     * @param $data - dane
     * @param $key - klucz do hashowania
     * @param $sign - hash danych
     *
     * @return bool
     */
    public function checkSign($data, $key, $sign)
    {
        return md5(
            $data['service'] .
                $data['orderid'] .
                $data['amount'] .
                $data['userdata'] .
                $data['status'] .
                $key
        ) == $sign;
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->data['key'];
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->data['service'];
    }
}
