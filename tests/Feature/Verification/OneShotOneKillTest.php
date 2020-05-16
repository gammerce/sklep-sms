<?php
namespace Tests\Feature\Verification;

use App\Managers\PaymentModuleManager;
use App\Requesting\Response;
use App\Verification\PaymentModules\OneShotOneKill;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Tests\Psr4\TestCases\TestCase;

class OneShotOneKillTest extends TestCase
{
    /** @var OneShotOneKill */
    private $oneShotOneKill;

    protected function setUp()
    {
        parent::setUp();

        /** @var PaymentModuleManager $paymentModuleManager */
        $paymentModuleManager = $this->app->make(PaymentModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => OneShotOneKill::MODULE_ID,
        ]);

        $this->oneShotOneKill = $paymentModuleManager->get($paymentPlatform);
    }

    /** @test */
    public function validates_proper_sms_code()
    {
        // given
        $this->requesterMock
            ->shouldReceive("get")
            ->withArgs(["http://www.1shot1kill.pl/api", Mockery::any()])
            ->andReturn(new Response(200, '{"status":"ok","amount":"16.25"}'));

        // when
        $result = $this->oneShotOneKill->verifySms("foobar", "92555");

        // then
        $this->assertInstanceOf(SmsSuccessResult::class, $result);
        $this->assertFalse($result->isFree());
    }
}
