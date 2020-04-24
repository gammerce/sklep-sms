<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\ServiceCodeRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceCodeCollectionTest extends HttpTestCase
{
    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->serviceCodeRepository = $this->app->make(ServiceCodeRepository::class);
    }

    /** @test */
    public function creates_service_code()
    {
        // given
        $server = $this->factory->server();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/services/vippro/service_codes", [
            'code' => 'abcpo',
            'quantity' => 40,
            'uid' => null,
            'server_id' => $server->getId(),
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $serviceCode = $this->serviceCodeRepository->get($json['data']['id']);
        $this->assertNotNull($serviceCode);
        $this->assertNull($serviceCode->getUid());
        $this->assertSame($server->getId(), $serviceCode->getServerId());
        $this->assertSame("abcpo", $serviceCode->getCode());
        $this->assertSame(40, $serviceCode->getQuantity());
        $this->assertSame('vippro', $serviceCode->getServiceId());
        $this->assertNotNull($serviceCode->getTimestamp());
    }

    /** @test */
    public function fails_with_invalid_data()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/services/vippro/service_codes", [
            'quantity' => 'asd',
            'server_id' => 'asd',
            'uid' => 'asd',
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        // TODO Add more detailed assertion
    }
}
