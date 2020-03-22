<?php
namespace Tests\Feature\Services;

use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\Services\ExpiredUserServiceService;
use App\Support\Database;
use Tests\Psr4\TestCases\TestCase;

class ExpiredUserServiceServiceTest extends TestCase
{
    /** @var ExpiredUserServiceService */
    private $expiredUserServiceService;

    protected function setUp()
    {
        parent::setUp();
        $this->expiredUserServiceService = $this->app->make(ExpiredUserServiceService::class);
    }

    /** @test */
    public function deletes_everything_related_to_service()
    {
        // given
        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var ExtraFlagUserServiceRepository $extraFlagUserServiceRepository */
        $extraFlagUserServiceRepository = $this->app->make(ExtraFlagUserServiceRepository::class);

        $server = $this->factory->server();
        $extraFlagsUserService = $this->factory->extraFlagUserService([
            "seconds" => -10,
            "server_id" => $server->getId(),
        ]);

        // when
        $this->expiredUserServiceService->deleteExpired();

        // then
        $this->assertNull($extraFlagUserServiceRepository->get($extraFlagsUserService->getId()));
        $statement = $db->statement("SELECT * FROM `ss_user_service` WHERE `id` = ?");
        $statement->execute([$extraFlagsUserService->getId()]);
        $this->assertSame(0, $statement->rowCount());
        $statement = $db->statement(
            "SELECT * FROM `ss_user_service_extra_flags` WHERE `us_id` = ?"
        );
        $statement->execute([$extraFlagsUserService->getId()]);
        $this->assertSame(0, $statement->rowCount());
    }
}
