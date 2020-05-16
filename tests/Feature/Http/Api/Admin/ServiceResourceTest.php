<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceResourceTest extends HttpTestCase
{
    /** @var Service */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = $this->factory->service();
    }

    /** @test */
    public function updates_service()
    {
        // given
        /** @var ServiceRepository $repository */
        $repository = $this->app->make(ServiceRepository::class);
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->put("/api/admin/services/{$this->service->getId()}", [
            'new_id' => 'example',
            'name' => 'My Example',
            'module' => ExtraFlagsServiceModule::MODULE_ID,
            'order' => 1,
            'web' => 1,
            'flags' => 'a',
            'type' => [ExtraFlagType::TYPE_NICK],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $service = $repository->get("example");
        $this->assertSame("My Example", $service->getName());
        $this->assertSame(ExtraFlagsServiceModule::MODULE_ID, $service->getModule());
    }

    /** @test */
    public function cannot_use_the_same_id_twice()
    {
        // given
        $this->actingAs($this->factory->admin());

        $id = 'example';
        $this->factory->service(compact('id'));

        // when
        $response = $this->put("/api/admin/services/{$this->service->getId()}", [
            'new_id' => $id,
            'name' => 'My Example',
            'module' => ExtraFlagsServiceModule::MODULE_ID,
            'order' => 1,
            'web' => 1,
            'flags' => 'a',
            'type' => [ExtraFlagType::TYPE_NICK],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }
}
