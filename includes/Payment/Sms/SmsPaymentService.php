<?php
namespace App\Payment\Sms;

use App\Loggers\DatabaseLogger;
use App\Models\User;
use App\Repositories\SmsCodeRepository;
use App\Support\Database;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

class SmsPaymentService
{
    /** @var Database */
    private $db;

    /** @var DatabaseLogger */
    private $logger;

    /** @var SmsPriceService */
    private $smsPriceService;

    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    public function __construct(
        Database $db,
        SmsPriceService $smsPriceService,
        SmsCodeRepository $smsCodeRepository,
        DatabaseLogger $logger
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->smsPriceService = $smsPriceService;
        $this->smsCodeRepository = $smsCodeRepository;
    }

    /**
     * @param SupportSms $paymentModule
     * @param string|null $code
     * @param int $price
     * @param User $user
     * @param string $ip
     * @param string $platform
     * @return int
     */
    public function payWithSms(SupportSms $paymentModule, $code, $price, User $user, $ip, $platform)
    {
        if ($price === 0) {
            return $this->storePaymentSms(
                $paymentModule,
                new SmsSuccessResult(false, 0),
                $code,
                $price,
                "",
                $ip,
                $platform
            );
        }

        $smsNumber = $this->smsPriceService->getNumber($price, $paymentModule);

        $result = $this->tryToUseSmsCode($code, $smsNumber->getPrice()->asInt());
        if ($result) {
            return $this->storePaymentSms(
                $paymentModule,
                $result,
                $code,
                $smsNumber->getPrice()->asInt(),
                $smsNumber->getNumber(),
                $ip,
                $platform
            );
        }

        try {
            $result = $paymentModule->verifySms($code, $smsNumber->getNumber());
        } catch (BadNumberException $e) {
            $smsPrice = $e->getSmsPrice();

            if ($smsPrice->asInt()) {
                $this->addSmsCodeToBeReused(
                    $code,
                    $smsPrice->asInt(),
                    $smsNumber->getPrice()->asInt(),
                    $user
                );
            }

            throw $e;
        } catch (SmsPaymentException $e) {
            $this->logger->log(
                "log_bad_sms_code_used",
                $code,
                $paymentModule->getSmsCode(),
                $smsNumber->getNumber(),
                $e->getErrorCode()
            );

            throw $e;
        }

        $smsPaymentId = $this->storePaymentSms(
            $paymentModule,
            $result,
            $code,
            $smsNumber->getPrice()->asInt(),
            $smsNumber->getNumber(),
            $ip,
            $platform
        );
        $this->logger->logWithUser(
            $user,
            "log_accepted_sms_code",
            $code,
            $paymentModule->getSmsCode(),
            $smsNumber->getNumber()
        );

        return $smsPaymentId;
    }

    /**
     * @param SupportSms $smsPaymentModule
     * @param SmsSuccessResult $result
     * @param string|null $code
     * @param int|null $price
     * @param string|null $number
     * @param string $ip
     * @param string $platform
     * @return string
     */
    private function storePaymentSms(
        SupportSms $smsPaymentModule,
        SmsSuccessResult $result,
        $code,
        $price,
        $number,
        $ip,
        $platform
    ) {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                    "VALUES (?,?,?,?,?,?,?,?)"
            )
            ->execute([
                $code,
                $this->smsPriceService->getProvision($price, $smsPaymentModule)->asInt(),
                $this->smsPriceService->getGross($price),
                $smsPaymentModule->getSmsCode(),
                $number,
                $ip,
                $platform,
                $result->isFree() ? 1 : 0,
            ]);

        return $this->db->lastId();
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
        $this->logger->log("log_payment_remove_code_from_db", $code, $smsPrice);

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

        $this->logger->logWithUser(
            $user,
            "log_add_code_to_reuse",
            $code,
            $smsPrice,
            $expectedSmsPrice
        );
    }
}
