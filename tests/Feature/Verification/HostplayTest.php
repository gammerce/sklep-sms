<?php
namespace Tests\Feature\Verification;

use App\Managers\PaymentModuleManager;
use App\Requesting\Response;
use App\Verification\PaymentModules\Hostplay;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Tests\Psr4\TestCases\TestCase;

class HostplayTest extends TestCase
{
    private Hostplay $hostplay;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PaymentModuleManager $paymentModuleManager */
        $paymentModuleManager = $this->app->make(PaymentModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Hostplay::MODULE_ID,
        ]);

        $this->hostplay = $paymentModuleManager->get($paymentPlatform);
    }

    /** @test */
    public function validates_proper_sms_code()
    {
        // given
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs(["http://hostplay.pl/api/payment/api_code_verify.php", Mockery::any()])
            ->andReturn(new Response(200, '{"status":"OK","kwota":"16.91"}'));

        // when
        $result = $this->hostplay->verifySms("foobar", "92555");

        // then
        $this->assertInstanceOf(SmsSuccessResult::class, $result);
        $this->assertFalse($result->isFree());
    }
}
