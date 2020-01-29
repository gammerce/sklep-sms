<?php
namespace Tests\Feature\Http\Api\Admin;

use App\Repositories\UserRepository;
use Tests\Psr4\TestCases\HttpTestCase;

class WalletChargeCollectionTest extends HttpTestCase
{
    /** @test */
    public function charge_wallet()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);
        $user = $this->factory->user([
            'wallet' => 1000,
        ]);

        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/users/{$user->getUid()}/wallet/charge", [
            "quantity" => 1,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("charged", $json["return_id"]);
        $freshUser = $userRepository->get($user->getUid());
        $this->assertSame(1100, $freshUser->getWallet());
    }

    /** @test */
    public function cannot_charge_if_invalid_uid()
    {
        // given
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->post("/api/admin/users/-1/wallet/charge", [
            "quantity" => 1,
        ]);

        // then
        $this->assertSame(200, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame("warnings", $json["return_id"]);
    }
}
