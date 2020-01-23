<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\SmsNumber;
use App\Models\User;
use App\Repositories\SmsCodeRepository;
use App\Services\SmsPriceService;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

class SmsPaymentService
{
    /** @var Translator */
    private $lang;

    /** @var Database */
    private $db;

    /** @var DatabaseLogger */
    private $logger;

    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    public function __construct(
        TranslationManager $translationManager,
        Database $db,
        SmsPriceService $smsPriceService,
        SmsCodeRepository $smsCodeRepository,
        DatabaseLogger $logger
    ) {
        $this->lang = $translationManager->user();
        $this->db = $db;
        $this->logger = $logger;
        $this->smsPriceService = $smsPriceService;
        $this->smsCodeRepository = $smsCodeRepository;
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
        SupportSms $smsPaymentModule,
        SmsSuccessResult $result,
        $code,
        SmsNumber $smsNumber,
        User $user
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                    "VALUES (?,?,?,?,?,?,?,?)"
            )
            ->execute([
                $code,
                $this->smsPriceService->getProvision($smsNumber->getPrice(), $smsPaymentModule),
                $this->smsPriceService->getGross($smsNumber->getPrice()),
                $smsPaymentModule->getSmsCode(),
                $smsNumber->getNumber(),
                $user->getLastIp(),
                $user->getPlatform(),
                $result->free ? 1 : 0,
            ]);

        $paymentId = $this->db->lastId();

        return [
            'status' => 'ok',
            'text' => $this->lang->t('sms_info_ok'),
            'payment_id' => $paymentId,
        ];
    }

    /**
     * @param string $code
     * @param int $smsPrice
     * @return SmsSuccessResult|null
     */
    private function tryToUseSmsCode($code, $smsPrice)
    {
        $smsCode = $this->smsCodeRepository->findByCodeAndPrice($code, $smsPrice);

        if (!$smsCode) {
            return null;
        }

        $this->smsCodeRepository->delete($smsCode->getId());
        $this->logger->log('payment_remove_code_from_db', $code, $smsPrice);

        return new SmsSuccessResult($smsCode->isFree());
    }

    /**
     * @param string $code
     * @param int $smsPrice
     * @param int $expectedSmsPrice
     * @param User $user
     */
    private function addSmsCodeToBeReused($code, $smsPrice, $expectedSmsPrice, User $user)
    {
        $this->smsCodeRepository->create($code, $smsPrice, false);

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
