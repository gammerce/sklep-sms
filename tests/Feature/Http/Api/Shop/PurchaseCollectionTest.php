<?php
namespace Tests\Feature\Http\Api\Shop;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseCollectionTest extends HttpTestCase
{
    /** @test */
    public function fails_when_no_data_passed()
    {
        // when
        $response = $this->post('/api/purchases', [
            'service_id' => 'vippro',
            'type' => ExtraFlagType::TYPE_IP,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json['return_id']);
        $this->assertEquals(
            [
                'auth_data' =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
                'email' =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
                'server_id' =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
                'password' =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
                'price_id' =>
                    '<ul class="form_warning help is-danger"><li >Pole nie może być puste.</li></ul>',
            ],
            $json['warnings']
        );
    }
}
