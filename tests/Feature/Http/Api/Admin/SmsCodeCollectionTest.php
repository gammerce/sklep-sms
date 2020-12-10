<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\SmsCodeRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class SmsCodeCollectionTest extends HttpTestCase
{
    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsCodeRepository = $this->app->make(SmsCodeRepository::class);
    }

    /** @test */
    public function creates_sms_code()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/sms_codes", [
            "code" => "sdsad",
            "sms_price" => 400,
            "expires_at" => "2020-02-02",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $smsCode = $this->smsCodeRepository->get($json["data"]["id"]);
        $this->assertSame("SDSAD", $smsCode->getCode());
        $this->assertEqualsMoney(400, $smsCode->getSmsPrice());
        $this->assertTrue($smsCode->isFree());
        $this->assertSame("2020-02-02 23:59", as_datetime_string($smsCode->getExpiresAt()));
    }

    /** @test */
    public function creates_sms_code_forever()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/sms_codes", [
            "code" => "POI123",
            "sms_price" => 200,
            "expires_at" => null,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $smsCode = $this->smsCodeRepository->get($json["data"]["id"]);
        $this->assertSame("POI123", $smsCode->getCode());
        $this->assertEqualsMoney(200, $smsCode->getSmsPrice());
        $this->assertNull($smsCode->getExpiresAt());
    }
}
