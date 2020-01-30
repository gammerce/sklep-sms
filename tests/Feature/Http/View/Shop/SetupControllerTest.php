<?php
namespace Tests\Feature\Http\View\Shop;

use App\Install\ShopState;
use App\Support\FileSystemContract;
use App\Support\Path;
use Mockery;
use Mockery\MockInterface;
use Tests\Psr4\TestCases\HttpTestCase;

class SetupControllerTest extends HttpTestCase
{
    /** @var FileSystemContract */
    private $fileSystem;

    /** @var ShopState|MockInterface */
    private $shopState;

    protected function setUp()
    {
        parent::setUp();
        $this->fileSystem = $this->mockFileSystem();
        $this->shopState = Mockery::mock(ShopState::class);
        $this->app->instance(ShopState::class, $this->shopState);
    }

    /** @test */
    public function shows_text_if_shop_is_up_to_date()
    {
        // given
        $this->markAsInstalled();

        // when
        $response = $this->get("/setup");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("Shop is up to date", $response->getContent());
    }

    /** @test */
    public function missing_env_if_upgrading_from_old_shop()
    {
        // given
        /** @var Path $path */
        $path = $this->app->make(Path::class);

        $this->markAsNotInstalled();
        $this->fileSystem->put($path->to("/includes/config.php"), "");

        // when
        $response = $this->get("/setup");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains("<title>Brak ENV - Sklep SMS</title>", $response->getContent());
    }

    /** @test */
    public function shows_install_form()
    {
        // given
        $this->markAsNotInstalled();

        // when
        $response = $this->get("/setup");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(
            "<h1 class=\"title is-4\">Instalator Sklepu SMS</h1>",
            $response->getContent()
        );
    }

    /** @test */
    public function shows_update_form()
    {
        // given
        $this->markAsNotUpToDate();

        // when
        $response = $this->get("/setup");

        // then
        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains(
            "<h1 class=\"title is-4\">Aktualizator Sklepu</h1>",
            $response->getContent()
        );
    }

    private function markAsInstalled()
    {
        $this->shopState->shouldReceive("isInstalled")->andReturnTrue();
        $this->shopState->shouldReceive("isUpToDate")->andReturnTrue();
    }

    private function markAsNotInstalled()
    {
        $this->shopState->shouldReceive("isInstalled")->andReturnFalse();
        $this->shopState->shouldReceive("isUpToDate")->andReturnFalse();
    }

    private function markAsNotUpToDate()
    {
        $this->shopState->shouldReceive("isInstalled")->andReturnTrue();
        $this->shopState->shouldReceive("isUpToDate")->andReturnFalse();
    }
}
