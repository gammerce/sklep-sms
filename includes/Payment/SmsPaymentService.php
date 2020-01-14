<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\SmsNumber;
use App\Models\User;
use App\Services\SmsPriceService;
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

    /** @var Database */
    private $db;

    /** @var DatabaseLogger */
    private $logger;

    /** @var SmsPriceService */
    private $smsPriceService;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        Database $db,
        SmsPriceService $smsPriceService,
        DatabaseLogger $logger
    ) {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->db = $db;
        $this->logger = $logger;
        $this->smsPriceService = $smsPriceService;
    }

    /**
     * @param SupportSms $paymentModule
     * @param string     $code
     * @param SmsNumber  $smsNumber
     * @param User       $user
     * @return array
     */
    public function payWithSms(SupportSms $paymentModule, $code, SmsNumber $smsNumber, User $user)
    {
        $result = $this->tryToUseSmsCode($code, $smsNumber->getPrice());
        if ($result) {
            return $this->storePaymentSms($paymentModule, $result, $code, $smsNumber, $user);
        }

        try {
            $result = $paymentModule->verifySms($code, $smsNumber->getNumber());
        } catch (BadNumberException $e) {
            if ($e->smsPrice !== null) {
                $this->addSmsCodeToBeReused($code, $e->smsPrice, $smsNumber->getPrice(), $user);
            }

            return [
                "status" => $e->getErrorCode(),
                "text" => $this->getSmsExceptionMessage($e),
            ];
        } catch (SmsPaymentException $e) {
            $this->logger->log(
                'bad_sms_code_used',
                $user->getUsername(),
                $user->getUid(),
                $user->getLastIp(),
                $code,
                $paymentModule->getSmsCode(),
                $smsNumber->getNumber(),
                $e->getErrorCode()
            );

            return [
                "status" => $e->getErrorCode(),
                "text" => $this->getSmsExceptionMessage($e),
            ];
        }

        return $this->storePaymentSms($paymentModule, $result, $code, $smsNumber, $user);
    }

    private function storePaymentSms(
        SupportSms $paymentModule,
        SmsSuccessResult $result,
        $code,
        SmsNumber $smsNumber,
        User $user
    ) {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                    "VALUES (?,?,?,?,?,?,?,?)"
            )
            ->execute([
                $code,
                $this->smsPriceService->getProvision($smsNumber->getPrice(), $paymentModule),
                $this->smsPriceService->getGross($smsNumber->getPrice()),
                $paymentModule->getSmsCode(),
                $smsNumber->getNumber(),
                $user->getLastIp(),
                $user->getPlatform(),
                $result->free,
            ]);

        $paymentId = $this->db->lastId();

        return [
            'status' => 'ok',
            'text' => $this->lang->t('sms_info_ok'),
            'payment_id' => $paymentId,
        ];
    }

    /**
     * @param string $smsCode
     * @param int $smsPrice
     * @return SmsSuccessResult|null
     */
    private function tryToUseSmsCode($smsCode, $smsPrice)
    {
        // TODO Migration changing tariff to sms_price
        // TODO Write test
        $statement = $this->db->statement(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "sms_codes` " .
                "WHERE `code` = ? AND `sms_price` = ?"
        );
        $statement->execute([$smsCode, $smsPrice]);

        if (!$statement->rowCount()) {
            return null;
        }

        $dbSmsCode = $statement->fetch();

        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "sms_codes` WHERE `id` = ?"
        );
        $statement->execute([$dbSmsCode['id']]);

        $this->logger->log('payment_remove_code_from_db', $smsCode, $smsPrice);

        return new SmsSuccessResult(!!$dbSmsCode['free']);
    }

    /**
     * @param string $code
     * @param int $smsPrice
     * @param int $expectedSmsPrice
     * @param User $user
     */
    private function addSmsCodeToBeReused($code, $smsPrice, $expectedSmsPrice, User $user)
    {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "sms_codes` " .
                    "SET `code` = ?, `sms_price` = ?, `free` = '0'"
            )
            ->execute([$code, $smsPrice]);

        $this->logger->log(
            'add_code_to_reuse',
            $code,
            $smsPrice,
            $user->getUsername(),
            $user->getUid(),
            $user->getLastIp(),
            $expectedSmsPrice
        );
    }

    private function getSmsExceptionMessage(SmsPaymentException $e)
    {
        return $e->getMessage() ?:
            $this->lang->t('sms_info_' . $e->getErrorCode()) ?:
            $e->getErrorCode();
    }
}
