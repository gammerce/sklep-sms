<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\ServerService;
use App\Models\Service;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\Other\OtherServiceModule;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceResourceTest extends HttpTestCase
{
    private ServiceRepository $serviceRepository;
    private ServerServiceRepository $serverServiceRepository;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->factory->extraFlagService();
        $this->serviceRepository = $this->app->make(ServiceRepository::class);
        $this->serverServiceRepository = $this->app->make(ServerServiceRepository::class);
    }

    /** @test */
    public function updates_service()
    {
        // given
        $this->actingAs($this->factory->admin());

        $serverFFA = $this->factory->server();
        $server4FUN = $this->factory->server();

        // when
        $response = $this->put("/api/admin/services/{$this->service->getId()}", [
            "new_id" => "example",
            "name" => "My Example",
            "module" => ExtraFlagsServiceModule::MODULE_ID,
            "order" => 1,
            "web" => 1,
            "flags" => "a",
            "type" => [ExtraFlagType::TYPE_NICK],
            "server_ids" => [$serverFFA->getId(), $server4FUN->getId()],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $service = $this->serviceRepository->get("example");
        $this->assertSame("My Example", $service->getName());
        $this->assertSame(ExtraFlagsServiceModule::MODULE_ID, $service->getModule());

        $links = $this->serverServiceRepository->findByService($service->getId());
        $serverIds = collect($links)
            ->map(fn(ServerService $serverService) => $serverService->getServerId())
            ->all();
        $this->assertEquals([$serverFFA->getId(), $server4FUN->getId()], $serverIds);
    }

    /** @test */
    public function cannot_use_the_same_id_twice()
    {
        // given
        $this->actingAs($this->factory->admin());

        $id = "example";
        $this->factory->extraFlagService(compact("id"));

        // when
        $response = $this->put("/api/admin/services/{$this->service->getId()}", [
            "new_id" => $id,
            "name" => "My Example",
            "module" => ExtraFlagsServiceModule::MODULE_ID,
            "order" => 1,
            "web" => 1,
            "flags" => "a",
            "type" => [ExtraFlagType::TYPE_NICK],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }

    /** @test */
    public function deletes_service()
    {
        // given
        $this->actingAs($this->factory->admin());
        $service = $this->factory->service([
            "id" => "example",
            "module" => OtherServiceModule::MODULE_ID,
        ]);

        // when
        $response = $this->delete("/api/admin/services/{$service->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshService = $this->serviceRepository->get($service->getId());
        $this->assertNull($freshService);
    }
}
