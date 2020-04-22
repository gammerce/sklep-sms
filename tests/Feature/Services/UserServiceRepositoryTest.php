<?php
namespace Tests\Feature\Services;

use App\Repositories\UserServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\System\Heart;
use Tests\Psr4\TestCases\TestCase;

class UserServiceRepositoryTest extends TestCase
{
    /** @var UserServiceRepository */
    private $userServiceRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->userServiceRepository = $this->app->make(UserServiceRepository::class);
    }

    /** @test */
    public function do_not_update_if_empty_data()
    {
        // given
        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        $serviceModule = $heart->getEmptyServiceModule(ExtraFlagsServiceModule::MODULE_ID);
        $userService = $this->factory->extraFlagUserService([
            "server_id" => $this->factory->server()->getId(),
        ]);

        // when
        $affected = $this->userServiceRepository->updateWithModule(
            $serviceModule,
            $userService->getId(),
            []
        );

        // then
        $this->assertSame(0, $affected);
    }
}
