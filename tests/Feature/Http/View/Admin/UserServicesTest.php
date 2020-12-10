<?php
namespace Tests\Feature\Http\View\Admin;

use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class UserServicesTest extends HttpTestCase
{
    use MakePurchaseConcern;

    /** @test */
    public function it_loads_extra_flags()
    {
        // given
        $this->createRandomExtraFlagsPurchase();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/user_service", [
            "subpage" => ExtraFlagsServiceModule::MODULE_ID,
            "search" => "e",
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            "<div class=\"title is-4\">Czasowe usługi użytkowników: Flagi Gracza",
            $response->getContent()
        );
    }

    /** @test */
    public function it_loads_mybb()
    {
        // given
        $this->createRandomMybbPurchase();
        $this->actingAs($this->factory->admin());

        // when
        $response = $this->get("/admin/user_service", [
            "subpage" => MybbExtraGroupsServiceModule::MODULE_ID,
            "search" => "e",
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Panel Admina", $response->getContent());
        $this->assertStringContainsString(
            "<div class=\"title is-4\">Czasowe usługi użytkowników: Grupy MyBB",
            $response->getContent()
        );
    }
}
