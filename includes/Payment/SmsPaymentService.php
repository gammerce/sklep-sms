<?php
namespace App\Payment;

use App\Models\Tariff;
use App\Models\User;
use App\System\Database;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

class SmsPaymentService
{
    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    /** @var Translator */
    private $langShop;

    /** @var Database */
    private $db;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        Database $db
    ) {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->db = $db;
    }

    /**
     * @param SupportSms $paymentModule
     * @param string        $code
     * @param Tariff        $tariff
     * @param User          $user
     * @return array
     */
    public function paySms(SupportSms $paymentModule, $code, Tariff $tariff, User $user)
    {
        $smsNumber = $tariff->getNumber();

        $result = $this->tryToUseSmsCode($code, $tariff);

        if ($result) {
            return $this->storePaymentSms($paymentModule, $result, $code, $smsNumber, $user);
        }

        try {
            $result = $paymentModule->verifySms($code, $smsNumber);
        } catch (BadNumberException $e) {
            if ($e->tariffId !== null) {
                $this->addSmsCode($code, $e->tariffId, $tariff, $user);
            }

            return [
                "status" => $e->getErrorCode(),
                "text" => $this->getSmsExceptionMessage($e),
            ];
        } catch (SmsPaymentException $e) {
            log_to_db(
                $this->langShop->sprintf(
                    $this->langShop->translate('bad_sms_code_used'),
                    $user->getUsername(),
                    $user->getUid(),
                    $user->getLastIp(),
                    $code,
                    $paymentModule->getSmsCode(),
                    $smsNumber,
                    $e->getErrorCode()
                )
            );

            return [
                "status" => $e->getErrorCode(),
                "text" => $this->getSmsExceptionMessage($e),
            ];
        }

        return $this->storePaymentSms($paymentModule, $result, $code, $smsNumber, $user);
    }

    private function storePaymentSms(SupportSms $paymentModule, SmsSuccessResult $result, $code, $smsNumber, User $user)
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                TABLE_PREFIX .
                "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                "VALUES ('%s','%d','%d','%s','%s','%s','%s','%d')",
                [
                    $code,
                    get_sms_cost($smsNumber) / 2,
                    ceil(get_sms_cost($smsNumber) * $this->settings['vat']),
                    $paymentModule->getSmsCode(),
                    $smsNumber,
                    $user->getLastIp(),
                    $user->getPlatform(),
                    $result->free,
                ]
            )
        );

        $paymentId = $this->db->lastId();

        return [
            'status' => 'ok',
            'text' => $this->lang->translate('sms_info_ok'),
            'payment_id' => $paymentId,
        ];
    }

    /**
     * @param string $smsCode
     * @param Tariff $tariff
     * @return SmsSuccessResult|null
     */
    private function tryToUseSmsCode($smsCode, Tariff $tariff)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                TABLE_PREFIX .
                "sms_codes` " .
                "WHERE `code` = '%s' AND `tariff` = '%d'",
                [$smsCode, $tariff->getId()]
            )
        );

        if (!$this->db->numRows($result)) {
            return null;
        }

        $dbCode = $this->db->fetchArrayAssoc($result);

        // Usuwamy kod z listy kodow do wykorzystania
        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "sms_codes` " . "WHERE `id` = '%d'",
                [$dbCode['id']]
            )
        );

        log_to_db(
            $this->langShop->sprintf(
                $this->langShop->translate('payment_remove_code_from_db'),
                $dbCode['code'],
                $dbCode['tariff']
            )
        );

        return new SmsSuccessResult(!!$dbCode['free']);
    }

    /**
     * @param string $code
     * @param int $tariffId
     * @param Tariff $expectedTariff
     * @param User $user
     */
    private function addSmsCode($code, $tariffId, Tariff $expectedTariff, User $user)
    {
        // Dodajemy kod do listy kodów do wykorzystania
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                TABLE_PREFIX .
                "sms_codes` " .
                "SET `code` = '%s', `tariff` = '%d', `free` = '0'",
                [$code, $tariffId]
            )
        );

        log_to_db(
            $this->langShop->sprintf(
                $this->langShop->translate('add_code_to_reuse'),
                $code,
                $tariffId,
                $user->getUsername(),
                $user->getUid(),
                $user->getLastIp(),
                $expectedTariff->getId()
            )
        );
    }

    private function getSmsExceptionMessage(SmsPaymentException $e)
    {
        if ($e->getMessage()) {
            return $e->getMessage();
        }

        $text = $this->lang->translate('sms_info_' . $e->getErrorCode());

        if (strlen($text)) {
            return $text;
        }

        return $e->getErrorCode();
    }
}