<?php
namespace App;

use App\Exceptions\InvalidConfigException;
use App\Models\Purchase;
use App\Models\Tariff;
use App\Models\User;
use App\System\Application;
use App\System\Database;
use App\System\Heart;
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

class Payment
{
    const TRANSFER_NOT_SUPPORTED = 'transfer_not_supported';

    /** @var PaymentModule|SupportSms|SupportTransfer */
    protected $paymentModule = null;

    /** @var Application */
    protected $app;

    /** @var Heart */
    protected $heart;

    /** @var Translator */
    protected $lang;

    /** @var Translator */
    protected $langShop;

    /** @var Settings */
    protected $settings;

    /** @var Database */
    protected $db;

    /** @var Path */
    protected $path;

    public function __construct($paymentModuleId)
    {
        $this->app = app();
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->heart = $this->app->make(Heart::class);
        $this->settings = $this->app->make(Settings::class);
        $this->db = $this->app->make(Database::class);
        $this->path = $this->app->make(Path::class);

        $this->paymentModule = $this->heart->getPaymentModule($paymentModuleId);

        if ($this->paymentModule === null) {
            throw new InvalidConfigException(
                // TODO Change translation
                $this->lang->sprintf(
                    $this->lang->translate('payment_bad_service'),
                    $paymentModuleId
                )
            );
        }
    }

    /**
     * @param string $code
     * @param Tariff $tariff
     * @param User $user
     *
     * @return array
     */
    public function paySms($code, Tariff $tariff, User $user)
    {
        $smsNumber = $tariff->getNumber();

        $result = $this->tryToUseSmsCode($code, $tariff);

        if ($result) {
            return $this->storePaymentSms($result, $code, $smsNumber, $user);
        }

        if (!$this->getPaymentModule()->supportSms()) {
            return [
                'status' => 'sms_not_supported',
                'text' => $this->lang->translate('sms_info_sms_not_supported'),
            ];
        }

        try {
            $result = $this->getPaymentModule()->verifySms($code, $smsNumber);
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
                    $this->getPaymentModule()->getSmsCode(),
                    $smsNumber,
                    $e->getErrorCode()
                )
            );

            return [
                "status" => $e->getErrorCode(),
                "text" => $this->getSmsExceptionMessage($e),
            ];
        }

        return $this->storePaymentSms($result, $code, $smsNumber, $user);
    }

    protected function storePaymentSms(SmsSuccessResult $result, $code, $smsNumber, User $user)
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
                    $this->getPaymentModule()->getSmsCode(),
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
    protected function tryToUseSmsCode($smsCode, Tariff $tariff)
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
    protected function addSmsCode($code, $tariffId, Tariff $expectedTariff, User $user)
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

    protected function getSmsExceptionMessage(SmsPaymentException $e)
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

    /**
     * @param Purchase $purchase
     *
     * @return array
     */
    public function payTransfer(Purchase $purchase)
    {
        if (!$this->getPaymentModule()->supportTransfer()) {
            return [
                'status' => Payment::TRANSFER_NOT_SUPPORTED,
                'text' => $this->lang->translate('transfer_' . Payment::TRANSFER_NOT_SUPPORTED),
            ];
        }

        $serialized = serialize($purchase);
        $dataFilename = time() . "-" . md5($serialized);
        file_put_contents($this->path->to('data/transfers/' . $dataFilename), $serialized);

        return [
            'status' => "transfer",
            'text' => $this->lang->translate('transfer_prepared'),
            'positive' => true,
            'data' => [
                'data' => $this->getPaymentModule()->prepareTransfer($purchase, $dataFilename),
            ],
            // Przygotowuje dane płatności transferem
        ];
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getSmsCode($escape = false)
    {
        return $escape
            ? htmlspecialchars($this->getPaymentModule()->getSmsCode())
            : $this->getPaymentModule()->getSmsCode();
    }

    /**
     * @return SupportSms|SupportTransfer|PaymentModule
     */
    public function getPaymentModule()
    {
        return $this->paymentModule;
    }
}
