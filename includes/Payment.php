<?php
namespace App;

use App\Models\Purchase;
use App\Models\Tariff;
use App\Models\TransferFinalize;
use App\Models\User;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;
use App\Services\Interfaces\IServicePurchase;

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

        // Tworzymy obiekt obslugujacy stricte weryfikacje
        $className = $this->heart->getPaymentModule($paymentModuleId);
        if ($className !== null) {
            $this->paymentModule = $this->app->make($className);
        }

        // API podanej usługi nie istnieje.
        if ($this->paymentModule === null) {
            output_page(
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
            log_info(
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

        log_info(
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

        log_info(
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
     * @param Purchase $purchaseData
     *
     * @return array
     */
    public function payTransfer($purchaseData)
    {
        if (!$this->getPaymentModule()->supportTransfer()) {
            return [
                'status' => Payment::TRANSFER_NOT_SUPPORTED,
                'text' => $this->lang->translate('transfer_' . Payment::TRANSFER_NOT_SUPPORTED),
            ];
        }

        $serialized = serialize($purchaseData);
        $data_filename = time() . "-" . md5($serialized);
        file_put_contents($this->app->path('data/transfers/' . $data_filename), $serialized);

        return [
            'status' => "transfer",
            'text' => $this->lang->translate('transfer_prepared'),
            'positive' => true,
            'data' => [
                'data' => $this->getPaymentModule()->prepareTransfer(
                    $purchaseData,
                    $data_filename
                ),
            ],
            // Przygotowuje dane płatności transferem
        ];
    }

    /**
     * @param TransferFinalize $transfer_finalize
     *
     * @return bool
     */
    public function transferFinalize($transfer_finalize)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " . "WHERE `id` = '%d'",
                [$transfer_finalize->getOrderid()]
            )
        );

        // Próba ponownej autoryzacji
        if ($this->db->numRows($result)) {
            return false;
        }

        // Nie znaleziono pliku z danymi
        if (
            !$transfer_finalize->getDataFilename() ||
            !file_exists(
                $this->app->path('data/transfers/' . $transfer_finalize->getDataFilename())
            )
        ) {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_no_data_file'),
                    $transfer_finalize->getOrderid()
                )
            );

            return false;
        }

        /** @var Purchase $purchaseData */
        $purchaseData = unserialize(
            file_get_contents(
                $this->app->path('data/transfers/' . $transfer_finalize->getDataFilename())
            )
        );

        // Fix: get user data again to avoid bugs linked with user wallet
        $purchaseData->user = $this->heart->getUser($purchaseData->user->getUid());

        // Dodanie informacji do bazy danych
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_transfer` " .
                    "SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
                [
                    $transfer_finalize->getOrderid(),
                    $purchaseData->getPayment('cost'),
                    $transfer_finalize->getTransferService(),
                    $purchaseData->user->getLastIp(),
                    $purchaseData->user->getPlatform(),
                ]
            )
        );
        unlink($this->app->path('data/transfers/' . $transfer_finalize->getDataFilename()));

        // Błędny moduł
        if (
            ($serviceModule = $this->heart->getServiceModule($purchaseData->getService())) ===
            null
        ) {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_bad_module'),
                    $transfer_finalize->getOrderid(),
                    $purchaseData->getService()
                )
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_no_purchase'),
                    $transfer_finalize->getOrderid(),
                    $purchaseData->getService()
                )
            );

            return false;
        }

        // Dokonujemy zakupu
        $purchaseData->setPayment([
            'method' => 'transfer',
            'payment_id' => $transfer_finalize->getOrderid(),
        ]);
        $bought_service_id = $serviceModule->purchase($purchaseData);

        log_info(
            $this->langShop->sprintf(
                $this->langShop->translate('payment_transfer_accepted'),
                $bought_service_id,
                $transfer_finalize->getOrderid(),
                $transfer_finalize->getAmount(),
                $transfer_finalize->getTransferService(),
                $purchaseData->user->getUsername(),
                $purchaseData->user->getUid(),
                $purchaseData->user->getLastIp()
            )
        );

        return true;
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
