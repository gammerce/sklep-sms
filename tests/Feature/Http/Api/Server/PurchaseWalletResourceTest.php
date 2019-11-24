<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\User;
use App\Services\ExtraFlags\ExtraFlagType;
use App\System\Settings;
use Tests\Psr4\TestCases\IndexTestCase;

class PurchaseWalletResourceTest extends IndexTestCase
{
    /** @test */
    public function purchase_using_wallet()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $serviceId = 'vip';
        $type = ExtraFlagType::TYPE_SID;
        $authData = 'STEAM_1:0:22309350';
        $ip = "192.0.2.1";
        $platform = "engine_amxx";
        $tariff = 2;

        $user = $this->factory->user([
            "steam_id" => $authData,
            "wallet" => 100,
        ]);

        $server = $this->factory->server();
        $this->factory->serverService([
            'server_id' => $server->getId(),
            'service_id' => $serviceId,
        ]);
        $this->factory->pricelist([
            'service_id' => $serviceId,
            'tariff' => $tariff,
            'server_id' => $server->getId(),
        ]);

        $sign = md5($authData . "#" . $settings->get("random_key"));

        // when
        $response = $this->post('/api/server/purchase/wallet', [
            'server' => $server->getId(),
            'service' => $serviceId,
            'type' => $type,
            'auth_data' => $authData,
            'ip' => $ip,
            'platform' => $platform,
            'tariff' => $tariff,
            'sign' => $sign,
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp(
            '#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona.</text><positive>1</positive><bsid>\d+</bsid>#',
            $response->getContent()
        );
        $freshUser = new User($user->getUid());
        $this->assertEquals(90, $freshUser->getWallet());
    }
}
