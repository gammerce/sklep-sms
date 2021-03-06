<?php
namespace Tests\Feature\Services;

use App\Repositories\ServerServiceRepository;
use App\Service\ServerServiceService;
use Tests\Psr4\TestCases\TestCase;

class ServerServiceServiceTest extends TestCase
{
    private ServerServiceService $serverServiceService;
    private ServerServiceRepository $serverServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverServiceService = $this->app->make(ServerServiceService::class);
        $this->serverServiceRepository = $this->app->make(ServerServiceRepository::class);
    }

    /** @test */
    public function updates_link_between_servers_and_services()
    {
        // given
        $serverA = $this->factory->server();
        $serverB = $this->factory->server();

        // when
        $this->serverServiceService->updateLinks([
            [
                "server_id" => $serverA->getId(),
                "service_id" => "vip",
                "connect" => true,
            ],
            [
                "server_id" => $serverB->getId(),
                "service_id" => "vippro",
                "connect" => true,
            ],
        ]);

        // then
        $serverServices = $this->serverServiceRepository->all();
        $this->assertCount(2, $serverServices);
    }

    /** @test */
    public function removes_link()
    {
        // given
        $serverA = $this->factory->server();
        $serverB = $this->factory->server();

        $this->serverServiceService->updateLinks([
            [
                "server_id" => $serverA->getId(),
                "service_id" => "vip",
                "connect" => true,
            ],
            [
                "server_id" => $serverB->getId(),
                "service_id" => "vippro",
                "connect" => true,
            ],
        ]);

        // when
        $this->serverServiceService->updateLinks([
            [
                "server_id" => $serverA->getId(),
                "service_id" => "vip",
                "connect" => false,
            ],
            [
                "server_id" => $serverA->getId(),
                "service_id" => "resnick",
                "connect" => true,
            ],
            [
                "server_id" => $serverB->getId(),
                "service_id" => "vippro",
                "connect" => true,
            ],
        ]);

        // then
        $serverServices = $this->serverServiceRepository->all();
        $this->assertCount(2, $serverServices);
    }
}
