<?php
namespace Tests\Feature\Repositories;

use App\Repositories\SmsCodeRepository;
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
        $smsCode = $this->smsCodeRepository->create("ABCD", 50, true, $date);

        // then
        $this->assertSame("ABCD", $smsCode->getCode());
        $this->assertSame(50, $smsCode->getSmsPrice());
        $this->assertTrue($smsCode->isFree());
        $this->assertSame($date->getTimestamp(), $date->getTimestamp());
    }

    /** @test */
    public function creates_sms_code_forever()
    {
        // when
        $smsCode = $this->smsCodeRepository->create("ABCD", 100, true, null);

        // then
        $this->assertSame("ABCD", $smsCode->getCode());
        $this->assertSame(100, $smsCode->getSmsPrice());
        $this->assertTrue($smsCode->isFree());
        $this->assertNull($smsCode->getExpiresAt());
    }
}
