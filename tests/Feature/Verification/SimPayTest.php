<?php
namespace Tests\Feature\Verification;

use App\Managers\PaymentModuleManager;
use App\Requesting\Response;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\CustomErrorException;
use App\Verification\PaymentModules\SimPay;
use App\Verification\Results\SmsSuccessResult;
use Tests\Psr4\TestCases\TestCase;

class SimPayTest extends TestCase
{
    private SimPay $simPay;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PaymentModuleManager $paymentModuleManager */
        $paymentModuleManager = $this->app->make(PaymentModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
            "data" => [
                "service_id" => "1",
                "key" => "foo",
                "secret" => "bar",
            ],
        ]);

        $this->simPay = $paymentModuleManager->get($paymentPlatform);
    }

    /** @test */
    public function validates_proper_sms_code()
    {
        // given
        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.simpay.pl/sms/1",
                json_encode(["code" => "63D9C7", "number" => "92555"]),
                [
                    "X-SIM-KEY" => "foo",
                    "X-SIM-PASSWORD" => "bar",
                    "Content-Type" => "application/json",
                ],
            ])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        "success" => true,
                        "data" => [
                            "used" => false,
                            "code" => "63D9C7",
                            "test" => false,
                            "from" => "123123123",
                            "number" => 92555,
                            "value" => 0.5,
                        ],
                    ])
                )
            );

        // when
        $result = $this->simPay->verifySms("63D9C7", "92555");

        // then
        $this->assertInstanceOf(SmsSuccessResult::class, $result);
        $this->assertFalse($result->isFree());
    }

    /** @test */
    public function throw_exception_on_bad_code()
    {
        // given
        $this->expectException(BadCodeException::class);

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.simpay.pl/sms/1",
                json_encode(["code" => "63D9C7", "number" => "72480"]),
                [
                    "X-SIM-KEY" => "foo",
                    "X-SIM-PASSWORD" => "bar",
                    "Content-Type" => "application/json",
                ],
            ])
            ->andReturn(
                new Response(
                    404,
                    json_encode([
                        "success" => false,
                        "message" => "Nieprawidłoy kod",
                    ])
                )
            );

        // when
        $this->simPay->verifySms("63D9C7", "72480");
    }

    /** @test */
    public function throw_exception_on_custom_error()
    {
        // given
        $this->expectException(CustomErrorException::class);
        $this->expectExceptionMessage("Wystąpił błąd podczas autoryzacji");

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.simpay.pl/sms/1",
                json_encode(["code" => "63D9C7", "number" => "72480"]),
                [
                    "X-SIM-KEY" => "foo",
                    "X-SIM-PASSWORD" => "bar",
                    "Content-Type" => "application/json",
                ],
            ])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        "success" => false,
                        "message" => "Wystąpił błąd podczas autoryzacji",
                    ])
                )
            );

        // when
        $this->simPay->verifySms("63D9C7", "72480");
    }
}
