<?php
namespace Tests\Feature\Verification;

use App\Requesting\Response;
use App\Verification\Cssetti;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Tests\Psr4\Concerns\FixtureConcern;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\TestCases\ServerTestCase;

class CssettiTest extends ServerTestCase
{
    use RequesterConcern;
    use FixtureConcern;

    /** @var Cssetti */
    private $cssetti;

    protected function setUp()
    {
        parent::setUp();

        $this->mockRequester();
        $this->cssetti = $this->app->make(Cssetti::class);

        $smsDataResponse = $this->loadFixture("cssetti_sms_api_v2_get_data");
        $this->requesterMock
            ->shouldReceive('get')
            ->withArgs(['https://cssetti.pl/Api/SmsApiV2GetData.php'])
            ->andReturn(new Response(200, $smsDataResponse));
    }

    /** @test */
    public function validates_proper_sms_code()
    {
        // given
        $this->requesterMock
            ->shouldReceive('get')
            ->withArgs(['https://cssetti.pl/Api/SmsApiV2CheckCode.php', Mockery::any()])
            ->andReturn(new Response(200, "1"));

        // when
        $result = $this->cssetti->verifySms("foobar", "72480");

        // then
        $this->assertInstanceOf(SmsSuccessResult::class, $result);
        $this->assertFalse($result->free);
    }

    /** @test */
    public function returns_sms_code()
    {
        // when
        $smsCode = $this->cssetti->getSmsCode();

        // then
        $this->assertEquals("SKLEP", $smsCode);
    }
}
