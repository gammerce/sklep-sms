<?php
namespace Tests\Feature;

use App\Exceptions\LicenseRequestException;
use App\System\License;
use App\Requesting\Response;
use App\Services\ExtraFlags\ExtraFlagType;
use App\System\Settings;
use App\Verification\Gosetti;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Tests\Psr4\Concerns\RequesterConcern;
use Tests\Psr4\TestCases\IndexTestCase;

class PurchaseServiceFromServerTest extends IndexTestCase
{
    use RequesterConcern;

    protected function setUp()
    {
        parent::setUp();
        $this->mockRequester();
    }

    /** @test */
    public function player_can_purchase_service()
    {
        // given
        $serviceId = 'vip';
        $tariff = 2;
        $transactionService = 'gosetti';
        $type = ExtraFlagType::TYPE_NICK;
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $method = 'sms';
        $uid = 0;
        $platform = 'engine_amxx';

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

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $query = [
            'key' => md5($settings->get('random_key')),
            'action' => 'purchase_service',
            'service' => $serviceId,
            'transaction_service' => $transactionService,
            'server' => $server->getId(),
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'sms_code' => $smsCode,
            'method' => $method,
            'tariff' => $tariff,
            'uid' => $uid,
            'platform' => $platform,
        ];

        $this->mockGoSetti();

        // when
        $response = $this->get('/servers_stuff.php', $query);

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp(
            '#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona.</text><positive>1</positive><bsid>\d+</bsid>#',
            $response->getContent()
        );
    }

    /** @test */
    public function player_cannot_make_a_purchase_if_license_is_invalid()
    {
        // given
        $license = $this->app->make(License::class);
        $license->shouldReceive('isValid')->andReturn(false);
        $license->shouldReceive('getLoadingException')->andReturn(new LicenseRequestException());

        // when
        $response = $this->get('/servers_stuff.php');

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($json, [
            "message" => "Coś poszło nie tak podczas łączenia się z serwerem weryfikacyjnym.",
        ]);
    }

    protected function mockGoSetti()
    {
        $this->requesterMock
            ->shouldReceive('get')
            ->withArgs(['https://gosetti.pl/Api/SmsApiV2GetData.php'])
            ->andReturn(
                new Response(
                    200,
                    json_encode([
                        'Code' => 'abc123',
                        'Numbers' => [],
                    ])
                )
            );

        $gosetti = Mockery::mock($this->app->make(Gosetti::class))->makePartial();
        $gosetti->shouldReceive('verifySms')->andReturn(new SmsSuccessResult());
        $this->app->instance(Gosetti::class, $gosetti);
    }
}
