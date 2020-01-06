<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\ServerRepository;
use App\Repositories\SettingsRepository;
use App\Verification\PaymentModules\Microsms;
use Tests\Psr4\TestCases\HttpTestCase;

class ServerCollectionTest extends HttpTestCase
{
    /** @var ServerRepository */
    private $serverRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->serverRepository = $this->app->make(ServerRepository::class);
    }

    /** @test */
    public function creates_server()
    {
        // given
        $this->actingAs($this->factory->admin());

        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Microsms::MODULE_ID,
        ]);

        // when
        $response = $this->post("/api/admin/servers", [
            'name' => 'My Example',
            'ip' => '192.168.0.1',
            'port' => '27015',
            'sms_platform' => $paymentPlatform->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $server = $this->serverRepository->get($json["data"]["id"]);
        $this->assertSame("My Example", $server->getName());
        $this->assertSame("192.168.0.1", $server->getIp());
        $this->assertSame("27015", $server->getPort());
        $this->assertSame($paymentPlatform->getId(), $server->getSmsPlatformId());
    }

    /** @test */
    public function set_default_sms_platform()
    {
        // given
        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->app->make(SettingsRepository::class);

        $this->actingAs($this->factory->admin());

        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Microsms::MODULE_ID,
        ]);

        $settingsRepository->update([
            "sms_platform" => $paymentPlatform->getId(),
        ]);

        // when
        $response = $this->post("/api/admin/servers", [
            'name' => 'My Example',
            'ip' => '192.168.0.1',
            'port' => '27015',
            'sms_platform' => null,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $server = $this->serverRepository->get($json["data"]["id"]);
        $this->assertNotNull($server);
    }

    /** @test */
    public function cannot_set_default_sms_platform_if_not_set_in_settings()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/servers", [
            'name' => 'My Example',
            'ip' => '192.168.0.1',
            'port' => '27015',
            'sms_platform' => null,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }
}
