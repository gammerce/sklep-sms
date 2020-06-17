<?php
namespace Tests\Feature\Http\Api\Shop;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use Tests\Psr4\Concerns\MybbRepositoryConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class PurchaseCollectionTest extends HttpTestCase
{
    use MybbRepositoryConcern;

    /** @test */
    public function extra_flags_fails_when_no_data_passed()
    {
        // when
        $response = $this->post("/api/purchases", [
            "service_id" => "vippro",
            "type" => ExtraFlagType::TYPE_IP,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "auth_data" => ["Pole nie może być puste."],
                "email" => ["Pole nie może być puste."],
                "server_id" => ["Pole nie może być puste."],
                "password" => ["Pole nie może być puste."],
            ],
            $json["warnings"]
        );
    }

    /** @test */
    public function mybb_fails_when_invalid_data_passed()
    {
        // given
        $this->mockMybbRepository();
        $this->mybbRepositoryMock
            ->shouldReceive("existsByUsername")
            ->withArgs(["test"])
            ->andReturnFalse();

        $service = $this->factory->mybbService();

        // when
        $response = $this->post("/api/purchases", [
            "service_id" => $service->getId(),
            "username" => "test",
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
        $this->assertEquals(
            [
                "email" => ["Pole nie może być puste."],
                "username" => ["Nie ma użytkownika o takiej nazwie."],
            ],
            $json["warnings"]
        );
    }
}
