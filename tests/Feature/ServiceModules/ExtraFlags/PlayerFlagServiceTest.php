<?php
namespace Tests\Feature\ServiceModules\ExtraFlags;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\ServiceModules\ExtraFlags\PlayerFlagService;
use Tests\Psr4\TestCases\TestCase;

class PlayerFlagServiceTest extends TestCase
{
    /** @var PlayerFlagService */
    private $playerFlagService;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->playerFlagService = $this->app->make(PlayerFlagService::class);
        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
    }

    /** @test */
    public function extend_service_that_never_expires()
    {
        // given
        $serviceId = "vip";
        $userId = null;
        $type = ExtraFlagType::TYPE_SID;
        $authData = "STEAM_1:1:1234124";
        $server = $this->factory->server();

        $userService = $this->factory->extraFlagUserService([
            "service_id" => $serviceId,
            "server_id" => $server->getId(),
            "user_id" => $userId,
            "seconds" => null,
            "type" => $type,
            "auth_data" => $authData,
            "password" => "",
        ]);

        // when
        $this->playerFlagService->addPlayerFlags(
            $serviceId,
            $server->getId(),
            5,
            $type,
            $authData,
            null,
            $userId
        );

        // then
        $freshUserService = $this->extraFlagUserServiceRepository->get($userService->getId());
        $this->assertSame(-1, $freshUserService->getExpire());
    }
}
