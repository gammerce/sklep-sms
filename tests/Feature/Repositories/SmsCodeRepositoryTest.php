<?php
namespace Tests\Feature\Repositories;

use App\Repositories\SmsCodeRepository;
use App\Support\Money;
use DateTime;
use Tests\Psr4\TestCases\TestCase;

class SmsCodeRepositoryTest extends TestCase
{
    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->smsCodeRepository = $this->app->make(SmsCodeRepository::class);
    }

    /** @test */
    public function creates_sms_code_fixed()
    {
        // given
        $date = new DateTime();

        // when
        $smsCode = $this->smsCodeRepository->create("ABCD", new Money(50), true, $date);

        // then
        $this->assertSame("ABCD", $smsCode->getCode());
        $this->assertEqualsMoney(50, $smsCode->getSmsPrice());
        $this->assertTrue($smsCode->isFree());
        $this->assertSame($date->getTimestamp(), $smsCode->getExpiresAt()->getTimestamp());
    }

    /** @test */
    public function creates_sms_code_forever()
    {
        // when
        $smsCode = $this->smsCodeRepository->create("ABCD", new Money(100), true, null);

        // then
        $this->assertSame("ABCD", $smsCode->getCode());
        $this->assertEqualsMoney(100, $smsCode->getSmsPrice());
        $this->assertTrue($smsCode->isFree());
        $this->assertNull($smsCode->getExpiresAt());
    }
}
