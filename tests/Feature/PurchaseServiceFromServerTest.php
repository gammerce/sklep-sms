<?php
namespace Tests\Feature;

use App\Settings;
use IPayment_Sms;
use PaymentModule_Gosetti;
use Tests\Psr4\ServerTestCase;

class PurchaseServiceFromServerTest extends ServerTestCase
{
    /** @test */
    public function player_can_purchase_service()
    {
        // given
        $serviceId = 'vip';
        $tariff = 2;
        $transactionService = 'gosetti';
        $type = TYPE_NICK;
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $method = 'sms';
        $uid = 0;
        $platform = 'engine_amxx';

        $server = $this->factory->server();
        $this->factory->serverService([
            'server_id'  => $server->getId(),
            'service_id' => $serviceId,
        ]);
        $this->factory->pricelist([
            'service_id' => $serviceId,
            'tariff'     => $tariff,
            'server_id'  => $server->getId(),
        ]);

        $query = http_build_query([
            'key'                 => md5($this->app->make(Settings::class)->get('random_key')),
            'action'              => 'purchase_service',
            'service'             => $serviceId,
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