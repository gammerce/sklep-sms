<?php
namespace Tests\Feature\Services;

use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\ServiceModules\MybbExtraGroups\MybbUserServiceRepository;
use App\Service\ExpiredUserServiceService;
use Tests\Psr4\Concerns\MybbRepositoryConcern;
use Tests\Psr4\TestCases\TestCase;

class ExpiredUserServiceServiceTest extends TestCase
{
    use MybbRepositoryConcern;

    private ExpiredUserServiceService $expiredUserServiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->expiredUserServiceService = $this->app->make(ExpiredUserServiceService::class);
    }

    /** @test */
    public function deletes_extra_flags()
    {
        // given
        /** @var ExtraFlagUserServiceRepository $extraFlagUserServiceRepository */
        $extraFlagUserServiceRepository = $this->app->make(ExtraFlagUserServiceRepository::class);

        $server = $this->factory->server();
        $userService = $this->factory->extraFlagUserService([
            "seconds" => -10,
            "server_id" => $server->getId(),
        ]);

        // when
        $this->expiredUserServiceService->deleteExpired();

        // then
        $this->assertNull($extraFlagUserServiceRepository->get($userService->getId()));
        $this->assertDatabaseDoesntHave("ss_user_service", [
            "id" => $userService->getId(),
        ]);
        $this->assertDatabaseDoesntHave("ss_user_service_extra_flags", [
            "us_id" => $userService->getId(),
        ]);
    }

    /** @test */
    public function deletes_mybb_extra_groups()
    {
        // given
        $this->mockMybbRepository();

        $this->mybbRepositoryMock
            ->shouldReceive("updateGroups")
            ->withArgs([1, [1, 2], 1])
            ->andReturnNull();

        /** @var MybbUserServiceRepository $mybbUserServiceRepository */
        $mybbUserServiceRepository = $this->app->make(MybbUserServiceRepository::class);

        $userService = $this->factory->mybbUserService([
            "seconds" => -10,
        ]);

        // when
        $this->expiredUserServiceService->deleteExpired();

        // then
        $this->assertNull($mybbUserServiceRepository->get($userService->getId()));
        $this->assertDatabaseDoesntHave("ss_user_service", [
            "id" => $userService->getId(),
        ]);
        $this->assertDatabaseDoesntHave("ss_user_service_mybb_extra_groups", [
            "us_id" => $userService->getId(),
        ]);
    }
}
