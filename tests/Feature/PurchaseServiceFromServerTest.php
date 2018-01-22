<?php
namespace Tests\Feature;

use App\Database;
use App\Entity\Pricelist;
use IPayment_Sms;
use PaymentModule_Gosetti;
use Tests\ServerTestCase;

class PurchaseServiceFromServerTest extends ServerTestCase
{
    /** @test */
    public function abc123()
    {
        // given
        /** @var Database $db */
        $db = $this->app->make(Database::class);
        $db->query("INSERT INTO ss_logs SET text = 'abc123'");

        // when


        // then

    }

    /** @test */
    public function player_can_purchase_service()
    {
        // given
        $key = '630f889b7d0f479a6a408385d000ce08';
        $action = 'purchase_service';
        $service = 'vip';
        $transactionService = 'gosetti';
        $server = 1;
        $type = 1;
        $authData = 'test';
        $password = 'test123';
        $smsCode = 'ABCD12EF';
        $method = 'sms';
        $tariff = 2;
        $uid = 0;
        $platform = 'engine_amxx';

        $query = http_build_query([
            'key'                 => $key,
            'action'              => $action,
            'service'             => $service,
            'transaction_service' => $transactionService,
            'server'              => $server,
            'type'                => $type,
            'auth_data'           => $authData,
            'password'            => $password,
            'sms_code'            => $smsCode,
            'method'              => $method,
            'tariff'              => $tariff,
            'uid'                 => $uid,
            'platform'            => $platform,
        ]);

        Pricelist::create($service, $tariff, 20, $server);
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