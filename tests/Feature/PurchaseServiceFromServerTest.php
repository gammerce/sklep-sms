<?php
namespace Tests\Feature;

use Tests\ServerTestCase;

class PurchaseServiceFromServerTest extends ServerTestCase
{
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
        $smsCode = 'ABCD';
        $method = 'sms';
        $tariff = '2';
        $uid = 0;
        $platform = 'engine_amxx';

        $query = http_build_query([
            'key' => $key,
            'action' => $action,
            'service' => $service,
            'transaction_service' => $transactionService,
            'server' => $server,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'sms_code' => $smsCode,
            'method' => $method,
            'tariff' => $tariff,
            'uid' => $uid,
            'platform' => $platform
        ]);

        // when
        $response = $this->call('GET', "?$query");

        // then
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('test', $response->getContent());
    }
}