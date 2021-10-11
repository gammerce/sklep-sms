<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Models\ServerService;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Verification\PaymentModules\CashBill;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Tests\Psr4\TestCases\HttpTestCase;

class ServerResourceTest extends HttpTestCase
{
    private ServerRepository $serverRepository;
    private ServerServiceRepository $serverServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverRepository = $this->app->make(ServerRepository::class);
        $this->serverServiceRepository = $this->app->make(ServerServiceRepository::class);
    }

    /** @test */
    public function updates_server()
    {
        // given
        $this->actingAs($this->factory->admin());
        $server = $this->factory->server();

        $smsPaymentPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $transferPaymentPlatform1 = $this->factory->paymentPlatform([
            "module" => CashBill::MODULE_ID,
        ]);
        $transferPaymentPlatform2 = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);

        // when
        $response = $this->put("/api/admin/servers/{$server->getId()}", [
            "name" => "My Example2",
            "ip" => "192.168.0.2",
            "port" => "27016",
            "sms_platform" => $smsPaymentPlatform->getId(),
            "transfer_platform" => [
                $transferPaymentPlatform1->getId(),
                $transferPaymentPlatform2->getId(),
            ],
            "service_ids" => ["vip", "vippro"],
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshServer = $this->serverRepository->get($server->getId());
        $this->assertSame("My Example2", $freshServer->getName());
        $this->assertSame("192.168.0.2", $freshServer->getIp());
        $this->assertSame("27016", $freshServer->getPort());
        $this->assertSame("192.168.0.2:27016", $freshServer->getAddress());
        $this->assertSame($smsPaymentPlatform->getId(), $freshServer->getSmsPlatformId());
        $this->assertSame(
            [$transferPaymentPlatform1->getId(), $transferPaymentPlatform2->getId()],
            $freshServer->getTransferPlatformIds()
        );

        $links = $this->serverServiceRepository->findByServer($server->getId());
        $serviceIds = collect($links)
            ->map(fn(ServerService $serverService) => $serverService->getServiceId())
            ->all();
        $this->assertEquals(["vip", "vippro"], $serviceIds);
    }

    /** @test */
    public function deletes_server()
    {
        // given
        $this->actingAs($this->factory->admin());
        $server = $this->factory->server();

        // when
        $response = $this->delete("/api/admin/servers/{$server->getId()}");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $freshServer = $this->serverRepository->get($server->getId());
        $this->assertNull($freshServer);
    }
}
