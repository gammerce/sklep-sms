<?php
namespace Tests\Feature\Http\Api\Server;

use App\Exceptions\LicenseRequestException;
use App\Models\Purchase;
use App\Models\Server;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\UserRepository;
use App\Services\ExtraFlags\ExtraFlagType;
use App\System\License;
use App\System\Settings;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseResourceWalletTest extends HttpTestCase
{
    /** @var Settings */
    private $settings;

    /** @var BoughtServiceRepository */
    private $boughtServiceRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var Server */
    private $server;

    private $serviceId = 'vip';
    private $tariff = 2;
    private $ip = "192.0.2.1";
    private $steamId = "STEAM_1:0:22309350";

    protected function setUp()
    {
        parent::setUp();

        $this->settings = $this->app->make(Settings::class);
        $this->boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);
        $this->userRepository = $this->app->make(UserRepository::class);

        $this->server = $this->factory->server();
        $this->factory->serverService([
            'server_id' => $this->server->getId(),
            'service_id' => $this->serviceId,
        ]);
        $this->factory->price([
            'service_id' => $this->serviceId,
            'tariff' => $this->tariff,
            'server_id' => $this->server->getId(),
        ]);
    }

    /** @test */
    public function purchase_using_wallet()
    {
        // given
        $user = $this->factory->user([
            "steam_id" => $this->steamId,
            "wallet" => 10000,
        ]);

        $sign = md5(
            implode("#", [
                ExtraFlagType::TYPE_SID,
                $this->steamId,
                "",
                $this->settings->get("random_key"),
            ])
        );

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'server' => $this->server->getId(),
                'service' => $this->serviceId,
                'type' => ExtraFlagType::TYPE_SID,
                'auth_data' => $this->steamId,
                'ip' => $this->ip,
                'tariff' => $this->tariff,
                'method' => Purchase::METHOD_WALLET,
                'sign' => $sign,
            ],
            [
                'key' => md5($this->settings->get("random_key")),
            ],
            [
                'Authorization' => $this->steamId,
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertRegExp(
            "#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona\.</text><positive>1</positive><bsid>\d+</bsid>#",
            $response->getContent()
        );

        preg_match("#<bsid>(\d+)</bsid>#", $response->getContent(), $matches);
        $boughtServiceId = intval($matches[1]);
        $boughtService = $this->boughtServiceRepository->get($boughtServiceId);
        $this->assertNotNull($boughtService);
        $this->assertEquals(Purchase::METHOD_WALLET, $boughtService->getMethod());

        $freshUser = $this->userRepository->get($user->getUid());
        $this->assertEquals(9860, $freshUser->getWallet());
    }

    /** @test */
    public function cannot_purchase_using_wallet_if_not_enough_money()
    {
        // given
        $user = $this->factory->user([
            "steam_id" => $this->steamId,
            "wallet" => 100,
        ]);

        $sign = md5(
            implode("#", [
                ExtraFlagType::TYPE_SID,
                $this->steamId,
                "",
                $this->settings->get("random_key"),
            ])
        );

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'server' => $this->server->getId(),
                'service' => $this->serviceId,
                'type' => ExtraFlagType::TYPE_SID,
                'auth_data' => $this->steamId,
                'ip' => $this->ip,
                'tariff' => $this->tariff,
                'method' => Purchase::METHOD_WALLET,
                'sign' => $sign,
            ],
            [
                'key' => md5($this->settings->get("random_key")),
            ],
            [
                'Authorization' => $this->steamId,
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertRegExp(
            "#<return_value>no_money</return_value><text>Bida! Nie masz wystarczającej ilości kasy w portfelu\. Doładuj portfel ;-\)</text><positive>0</positive>#",
            $response->getContent()
        );

        $freshUser = $this->userRepository->get($user->getUid());
        $this->assertEquals(100, $freshUser->getWallet());
    }

    /** @test */
    public function cannot_purchase_using_wallet_if_not_authorized()
    {
        // given
        $sign = md5(
            implode("#", [
                ExtraFlagType::TYPE_SID,
                $this->steamId,
                "",
                $this->settings->get("random_key"),
            ])
        );

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'server' => $this->server->getId(),
                'service' => $this->serviceId,
                'type' => ExtraFlagType::TYPE_SID,
                'auth_data' => $this->steamId,
                'ip' => $this->ip,
                'tariff' => $this->tariff,
                'method' => Purchase::METHOD_WALLET,
                'sign' => $sign,
            ],
            [
                'key' => md5($this->settings->get("random_key")),
            ],
            [
                'Authorization' => $this->steamId,
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertRegExp(
            "#<return_value>wallet_not_logged</return_value><text>Nie można zapłacić portfelem, gdy nie jesteś zalogowany.</text><positive>0</positive>#",
            $response->getContent()
        );
    }

    /** @test */
    public function cannot_make_a_purchase_if_license_is_invalid()
    {
        // given
        $license = $this->app->make(License::class);
        $license->shouldReceive('isValid')->andReturn(false);
        $license->shouldReceive('getLoadingException')->andReturn(new LicenseRequestException());

        // given
        $user = $this->factory->user([
            "steam_id" => $this->steamId,
            "wallet" => 10000,
        ]);

        $sign = md5(
            implode("#", [
                ExtraFlagType::TYPE_SID,
                $this->steamId,
                "",
                $this->settings->get("random_key"),
            ])
        );

        // when
        $response = $this->post(
            '/api/server/purchase',
            [
                'server' => $this->server->getId(),
                'service' => $this->serviceId,
                'type' => ExtraFlagType::TYPE_SID,
                'auth_data' => $this->steamId,
                'ip' => $this->ip,
                'tariff' => $this->tariff,
                'method' => Purchase::METHOD_WALLET,
                'sign' => $sign,
            ],
            [
                'key' => md5($this->settings->get("random_key")),
            ],
            [
                'Authorization' => $this->steamId,
                'User-Agent' => Server::TYPE_AMXMODX,
            ]
        );

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($json, [
            "message" => "Coś poszło nie tak podczas łączenia się z serwerem weryfikacyjnym.",
        ]);
        $freshUser = $this->userRepository->get($user->getUid());
        $this->assertEquals(10000, $freshUser->getWallet());
    }
}
