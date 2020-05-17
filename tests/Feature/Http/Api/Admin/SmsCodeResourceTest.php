<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\SmsCodeRepository;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class SmsCodeResourceTest extends HttpTestCase
{
    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->smsCodeRepository = $this->app->make(SmsCodeRepository::class);
    }

    /** @test */
    public function deletes_sms_code()
    {
        // given
        $this->actingAs($this->factory->admin());
        $smsCode = $this->factory->smsCode();

        // when
        $response = $this->delete("/api/admin/sms_codes/{$smsCode->getId()}");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $freshSmsCode = $this->smsCodeRepository->get($smsCode->getId());
        $this->assertNull($freshSmsCode);
    }
}
