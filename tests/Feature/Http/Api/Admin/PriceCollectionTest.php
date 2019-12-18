<?php
namespace Tests\Feature\Http\Api\Admin;

use Tests\Psr4\TestCases\HttpTestCase;

class PriceCollectionTest extends HttpTestCase
{
    /** @test */
    public function creates_price()
    {
        // given
        $admin = $this->factory->user(["groups" => 2]);
        $this->actAs($admin);

        // when
        $response = $this->post("/api/admin/prices", [
            'service' => '',
            'server' => '',
            'tariff' => 2,
            'amount' => 20,
        ]);

        // then
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function cannot_create_twice_the_same_price()
    {
        //
    }
}
