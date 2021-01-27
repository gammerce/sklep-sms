<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\ServerService;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceCollectionTest extends HttpTestCase
{
    private ServiceRepository $serviceRepository;
    private ServerServiceRepository $serverServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceRepository = $this->app->make(ServiceRepository::class);
        $this->serverServiceRepository = $this->app->make(ServerServiceRepository::class);
    }

    /** @test */
    public function creates_extra_flag_service()
    {
        // given
        $this->actingAs($this->factory->admin());

        $serverFFA = $this->factory->server();
        $server4FUN = $this->factory->server();

        // when
        $response = $this->post("/api/admin/services", [
            "id" => "example",
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
        $this->assertSame(
            [
                "web" => 1,
            ],
            $service->getData()
        );

        $links = $this->serverServiceRepository->findByService($service->getId());
        $serverIds = collect($links)
            ->map(fn(ServerService $serverService) => $serverService->getServerId())
            ->all();
        $this->assertEquals([$serverFFA->getId(), $server4FUN->getId()], $serverIds);
    }

    /** @test */
    public function creates_mybb_service()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/services", [
            "id" => "example",
            "name" => "My Example",
            "module" => MybbExtraGroupsServiceModule::MODULE_ID,
            "order" => 1,
            "web" => 1,
            "db_host" => "my_host",
            "db_user" => "my_user",
            "db_name" => "my_name",
            "db_password" => "abc123",
            "mybb_groups" => "9,10",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $service = $this->serviceRepository->get("example");
        $this->assertSame("My Example", $service->getName());
        $this->assertSame(MybbExtraGroupsServiceModule::MODULE_ID, $service->getModule());
        $this->assertSame(
            [
                "mybb_groups" => "9,10",
                "web" => 1,
                "db_host" => "my_host",
                "db_user" => "my_user",
                "db_password" => "abc123",
                "db_name" => "my_name",
            ],
            $service->getData()
        );

        $links = $this->serverServiceRepository->findByService($service->getId());
        $serverIds = collect($links)
            ->map(fn(ServerService $serverService) => $serverService->getServerId())
            ->all();
        $this->assertEquals([], $serverIds);
    }

    /** @test */
    public function cannot_use_the_same_id_twice()
    {
        // given
        $this->actingAs($this->factory->admin());

        $id = "example";
        $this->factory->extraFlagService(compact("id"));

        // when
        $response = $this->post("/api/admin/services", [
            "id" => $id,
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
}
