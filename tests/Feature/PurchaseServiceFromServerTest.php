<?php
namespace Tests\Feature;

use App\Models\Pricelist;
use App\Models\Server;
use App\Models\ServerService;
use App\Settings;
use IPayment_Sms;
use PaymentModule_Gosetti;
use Tests\ServerTestCase;

class PurchaseServiceFromServerTest extends ServerTestCase
{
    /** @test */
    public function player_can_purchase_service()
    {
        // given
        $service = 'vip';
        $tariff = 2;
        $transactionService = 'gosetti';
        $type = TYPE_NICK;
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $method = 'sms';
        $uid = 0;
        $platform = 'engine_amxx';

        $server = Server::create('test', '127.0.0.1', '27015');
        ServerService::create($server->getId(), $service);
        Pricelist::create($service, $tariff, 20, $server->getId());

        $query = http_build_query([
            'key'                 => md5(app()->make(Settings::class)->get('random_key')),
            'action'              => 'purchase_service',
            'service'             => $service,
            'transaction_service' => $transactionService,
            'server'              => $server->getId(),
            'type'                => $type,
            'auth_data'           => $authData,
            'password'            => $password,
            'sms_code'            => $smsCode,
            'method'              => $method,
            'tariff'              => $tariff,
            'uid'                 => $uid,
            'platform'            => $platform,
        ]);

        $this->mockGoSetti();

        // when
        $response = $this->call('GET', "?$query");

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp(
            '#<return_value>purchased</return_value><text>Usługa została prawidłowo zakupiona.</text><positive>1</positive><bsid>\d+</bsid>#',
            $response->getContent()
        );
    }

    protected function mockGoSetti()
    {
        $gosetti = \Mockery::mock(new PaymentModule_Gosetti())->makePartial();
        $gosetti->shouldReceive('verify_sms')->andReturn(IPayment_Sms::OK);
        $this->app->instance(PaymentModule_Gosetti::class, $gosetti);
    }
}