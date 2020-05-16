<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Payment\General\PaymentMethod;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Services\UserServiceService;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class ServiceTakeOverControllerTest extends HttpTestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function takes_over_a_service()
    {
        // given
        $boughtService = $this->createRandomPurchase([
            "auth_data" => "STEAM_1:0:22309350",
            "password" => null,
            "method" => PaymentMethod::SMS(),
            "sms_code" => "testcode",
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        /** @var UserServiceService $userServiceService */
        $userServiceService = $this->app->make(UserServiceService::class);

        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->post("/api/services/vip/take_over", [
            "auth_data" => "STEAM_1:0:22309350",
            "payment_method" => PaymentMethod::SMS(),
            "payment_id" => "testcode",
            "server_id" => $boughtService->getServerId(),
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("ok", $json["return_id"]);
        $userServices = $userServiceService->find();
        $this->assertCount(1, $userServices);
        $this->assertSame($user->getId(), $userServices[0]->getUserId());
    }

    /** @test */
    public function cannot_take_over_if_invalid_data()
    {
        // given
        $boughtService = $this->createRandomPurchase([
            "auth_data" => "STEAM_1:0:22309350",
            "password" => null,
            "method" => PaymentMethod::SMS(),
            "sms_code" => "testcod1",
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        /** @var UserServiceService $userServiceService */
        $userServiceService = $this->app->make(UserServiceService::class);

        $user = $this->factory->user();
        $this->actingAs($user);

        // when
        $response = $this->post("/api/services/vip/take_over", [
            "auth_data" => "STEAM_1:0:22309350",
            "payment_method" => PaymentMethod::SMS(),
            "payment_id" => "testcode",
            "server_id" => $boughtService->getServerId(),
            "type" => ExtraFlagType::TYPE_SID,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("no_service", $json["return_id"]);
        $userServices = $userServiceService->find();
        $this->assertCount(1, $userServices);
        $this->assertSame(0, $userServices[0]->getUserId());
    }
}
