<?php
namespace Tests\Feature\Verification;

use App\Requesting\Response;
use App\System\Heart;
use App\Verification\PaymentModules\Hostplay;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\TestCases\TestCase;

class HostplayTest extends TestCase
{
    use RequesterConcern;

    /** @var Hostplay */
    private $hostplay;

    protected function setUp()
    {
        parent::setUp();

        $this->mockRequester();

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Hostplay::MODULE_ID,
        ]);

        $this->hostplay = $heart->getPaymentModule($paymentPlatform);
    }

    /** @test */
    public function validates_proper_sms_code()
    {
        // given
        $this->requesterMock
            ->shouldReceive('get')
            ->withArgs(['http://hostplay.pl/api/payment/api_code_verify.php', Mockery::any()])
            ->andReturn(new Response(200, '{"status":"OK","kwota":"16.91"}'));

        // when
        $result = $this->hostplay->verifySms("foobar", "92555");

        // then
        $this->assertInstanceOf(SmsSuccessResult::class, $result);
        $this->assertFalse($result->isFree());
    }
}
