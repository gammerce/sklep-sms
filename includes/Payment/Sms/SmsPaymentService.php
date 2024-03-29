<?php
namespace App\Payment\Sms;

use App\Loggers\DatabaseLogger;
use App\Models\User;
use App\Repositories\SmsCodeRepository;
use App\Support\Database;
use App\Support\Money;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

class SmsPaymentService
{
    private Database $db;
    private DatabaseLogger $logger;
    private SmsPriceService $smsPriceService;
    private SmsCodeRepository $smsCodeRepository;

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
     * @param string $code
     * @param Money $price
     * @param User $user
     * @param string $ip
     * @param string $platform
     * @return int
     */
    public function payWithSms(
        SupportSms $paymentModule,
        $code,
        Money $price,
        User $user,
        $ip,
        $platform
    ): int {
        if ($price->equal(0)) {
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

        $result = $this->tryToUseSmsCode($code, $smsNumber->getPrice());
        if ($result) {
            return $this->storePaymentSms(
                $paymentModule,
                $result,
                $code,
                $smsNumber->getPrice(),
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
                $this->addSmsCodeToBeReused($code, $smsPrice, $smsNumber->getPrice(), $user);
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
            $smsNumber->getPrice(),
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
     * @param string $code
     * @param Money $price
     * @param string $number
     * @param string $ip
     * @param string $platform
     * @return int
     */
    private function storePaymentSms(
        SupportSms $smsPaymentModule,
        SmsSuccessResult $result,
        $code,
        Money $price,
        $number,
        $ip,
        $platform
    ): int {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                    "VALUES (?,?,?,?,?,?,?,?)"
            )
            ->bindAndExecute([
                $code,
                $this->smsPriceService->getProvision($price, $smsPaymentModule)->asInt(),
                $this->smsPriceService->getGross($price)->asInt(),
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
     * @param Money $smsPrice
     * @return SmsSuccessResult|null
     */
    private function tryToUseSmsCode($code, Money $smsPrice): ?SmsSuccessResult
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
     * @param Money $smsPrice
     * @param Money $expectedSmsPrice
     * @param User $user
     */
    private function addSmsCodeToBeReused(
        $code,
        Money $smsPrice,
        Money $expectedSmsPrice,
        User $user
    ): void {
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
