<?php
namespace Tests\Feature\Http\Api\Server;

use App\Models\Purchase;
use App\Models\User;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\UserRepository;
use App\Services\ExtraFlags\ExtraFlagType;
use App\System\Settings;
use Tests\Psr4\TestCases\IndexTestCase;

class PurchaseResourceTest extends IndexTestCase
{
    /** @test */
    public function purchase_using_wallet()
    {
        // given
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $serviceId = 'vip';
        $type = ExtraFlagType::TYPE_SID;
        $authData = 'STEAM_1:0:22309350';
        $ip = "192.0.2.1";
        $platform = "engine_amxx";
        $method = Purchase::METHOD_WALLET;
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

        $sign = md5($authData . "#" . "" . "#" . $settings->get("random_key"));

        // when
        $response = $this->post('/api/server/purchase', [
            'server' => $server->getId(),
            'service' => $serviceId,
            'type' => $type,
            'auth_data' => $authData,
            'ip' => $ip,
            'platform' => $platform,
            'tariff' => $tariff,
            'method' => $method,
            'sign' => $sign,
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());

        preg_match(
            "#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona.</text><positive>1</positive><bsid>(\d+)</bsid>#",
            $response->getContent(),
            $matches
        );
        $this->assertCount(2, $matches);

        $boughtServiceId = intval($matches[1]);
        $boughtService = $boughtServiceRepository->get($boughtServiceId);
        $this->assertNotNull($boughtService);
        $this->assertEquals(Purchase::METHOD_WALLET, $boughtService->getMethod());

        $freshUser = $userRepository->get($user->getUid());
        $this->assertEquals(90, $freshUser->getWallet());
    }
}
