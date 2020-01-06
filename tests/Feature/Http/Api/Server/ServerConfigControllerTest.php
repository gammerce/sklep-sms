<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\PaymentPlatform;
use App\Models\Server;
use App\System\Settings;
use App\Verification\PaymentModules\Cssetti;
use Tests\Psr4\TestCases\HttpTestCase;

class ServerConfigControllerTest extends HttpTestCase
{
    /** @var PaymentPlatform */
    private $paymentPlatform;

    /** @var Server */
    private $server;

    protected function setUp()
    {
        parent::setUp();
        $this->paymentPlatform = $this->factory->paymentPlatform([
            'module' => Cssetti::MODULE_ID,
        ]);
        $this->server = $this->factory->server(['sms_platform' => $this->paymentPlatform->getId()]);
    }

    /** @test */
    public function list_when_no_users()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        // when
        $response = $this->get('/api/server/config', [
            'key' => md5($settings->get("random_key")),
            'ip' => $this->server->getIp(),
            'port' => $this->server->getPort(),
            'type' => Server::TYPE_AMXMODX,
            'version' => '3.8.0',
        ]);

        // then
        $data = [
            "id:{$this->server->getId()}",
            "name:{$this->server->getName()}",
            "sms_platform_id:{$this->paymentPlatform->getId()}",
            "sms_module_id:cssetti",
            "services:  ",
            "steam_ids:;",
        ];
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(implode("\n", $data), $response->getContent());
    }

    /** @test */
    public function lists_users_steam_ids()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $this->factory->user([
            "steam_id" => "STEAM_1",
        ]);
        $this->factory->user([
            "steam_id" => "STEAM_12",
        ]);
        $this->factory->user();
        $this->factory->user([
            "steam_id" => "STEAM_2",
        ]);

        // when
        $response = $this->get('api/server/config', [
            'key' => md5($settings->get("random_key")),
            'ip' => $this->server->getIp(),
            'port' => $this->server->getPort(),
            'type' => Server::TYPE_SOURCEMOD,
            'version' => '3.7.0',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("steam_ids:STEAM_1;STEAM_12;STEAM_2;", $response->getContent());
    }

    /** @test */
    public function returns_402_if_invalid_version()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        // when
        $response = $this->get('/api/server/config', [
            'key' => md5($settings->get("random_key")),
            'ip' => $this->server->getIp(),
            'port' => $this->server->getPort(),
            'type' => Server::TYPE_AMXMODX,
            'version' => '3.7.0',
        ]);

        // then
        $this->assertSame(402, $response->getStatusCode());
    }

    /** @test */
    public function returns_404_if_invalid_ip_port()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        // when
        $response = $this->get('/api/server/config', [
            'key' => md5($settings->get("random_key")),
            'ip' => '1.1.1.1',
            'port' => '11111',
            'type' => Server::TYPE_AMXMODX,
            'version' => '3.8.0',
        ]);

        // then
        $this->assertSame(404, $response->getStatusCode());
    }
}
