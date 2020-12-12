<?php
namespace Tests\Feature\System;

use App\Repositories\SmsCodeRepository;
use App\Support\Money;
use App\System\CronExecutor;
use DateTime;
use Tests\Psr4\TestCases\TestCase;

class CronExecutorTest extends TestCase
{
    /** @var CronExecutor */
    private $cronExecutor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cronExecutor = $this->app->make(CronExecutor::class);
    }

    /** @test */
    public function removes_expired_sms_codes()
    {
        // given
        /** @var SmsCodeRepository $smsCodeRepository */
        $smsCodeRepository = $this->app->make(SmsCodeRepository::class);
        $smsCode = $this->factory->smsCode([
            "free" => true,
            "expires" => new DateTime("2020-01-01 10:00:00"),
        ]);

        // when
        $this->cronExecutor->run();

        // then
        $freshSmsCode = $smsCodeRepository->get($smsCode->getId());
        $this->assertNull($freshSmsCode);
    }
}
