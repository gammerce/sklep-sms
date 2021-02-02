<?php
namespace App\ServiceModules\ExtraFlags;

use App\Managers\ServiceManager;
use App\Repositories\UserServiceRepository;
use App\Service\ExpiredUserServiceService;
use App\Support\Expression;

class PlayerFlagService
{
    private PlayerFlagRepository $playerFlagRepository;
    private ServiceManager $serviceManager;
    private ExtraFlagUserServiceRepository $extraFlagUserServiceRepository;
    private UserServiceRepository $userServiceRepository;
    private ExpiredUserServiceService $expiredUserServiceService;

    public function __construct(
        PlayerFlagRepository $playerFlagRepository,
        ExtraFlagUserServiceRepository $extraFlagUserServiceRepository,
        UserServiceRepository $userServiceRepository,
        ExpiredUserServiceService $expiredUserServiceService,
        ServiceManager $serviceManager
    ) {
        $this->playerFlagRepository = $playerFlagRepository;
        $this->serviceManager = $serviceManager;
        $this->extraFlagUserServiceRepository = $extraFlagUserServiceRepository;
        $this->userServiceRepository = $userServiceRepository;
        $this->expiredUserServiceService = $expiredUserServiceService;
    }

    /**
     * @param string $serviceId
     * @param int $serverId
     * @param int|null $days
     * @param string $type
     * @param string $authData
     * @param string|null $password
     * @param int|null $userId
     * @return void
     */
    public function addPlayerFlags(
        $serviceId,
        $serverId,
        $days,
        $type,
        $authData,
        $password,
        $userId
    ) {
        $authData = trim($authData);
        $password = strlen($password) ? $password : "";
        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $seconds = multiply($days, 24 * 60 * 60);

        // Let's delete expired data. Just in case, to avoid risk of conflicts.
        $this->expiredUserServiceService->deleteExpired();
        $this->playerFlagRepository->deleteOldFlags();

        // Let's add a user service. If there is service with the same data,
        // let's prolong the existing one.
        $userService = $this->extraFlagUserServiceRepository->find([
            "us.service_id" => $serviceId,
            "m.server_id" => $serverId,
            "m.type" => $type,
            "m.auth_data" => $authData,
        ]);

        if ($userService) {
            if ($seconds === null || $userService->isForever()) {
                $expire = null;
            } else {
                $expire = new Expression("`expire` + {$seconds}");
            }

            $this->userServiceRepository->updateWithModule($table, $userService->getId(), [
                "user_id" => $userId,
                "password" => $password,
                "expire" => $expire,
            ]);
        } else {
            $this->extraFlagUserServiceRepository->create(
                $serviceId,
                $userId,
                $seconds,
                $serverId,
                $type,
                $authData,
                $password
            );
        }

        // Let's set identical passwords for all services of that player on that server
        $this->extraFlagUserServiceRepository->updatePassword(
            $password,
            $serverId,
            $type,
            $authData
        );

        // Let's recalculate players flags since we've added new user service
        $this->recalculatePlayerFlags($serverId, $type, $authData);
    }

    /**
     * Refresh players flags
     *
     * @param int $serverId
     * @param int $type
     * @param string $authData
     */
    public function recalculatePlayerFlags($serverId, $type, $authData)
    {
        // The type has to be given, otherwise we will remove all player flags without a type
        // The script will do nothing without a server or authData
        if (!$type) {
            return;
        }

        // Delete all player flags matching criteria since we're going to create it from scratch
        $this->playerFlagRepository->deleteByCredentials($serverId, $type, $authData);

        // Get all player flags matching criteria
        $extraFlagUserServices = $this->extraFlagUserServiceRepository->findAll([
            "server_id" => $serverId,
            "type" => $type,
            "auth_data" => $authData,
            new Expression("( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )"),
        ]);

        // Let's calculate when each flag should expire
        $flags = [];
        $password = "";
        foreach ($extraFlagUserServices as $extraFlagUserService) {
            // Let's get one password we will use for all player flags
            if (!strlen($password)) {
                $password = $extraFlagUserService->getPassword();
            }

            $service = $this->serviceManager->get($extraFlagUserService->getServiceId());
            assert($service);

            foreach (str_split($service->getFlags()) as $flag) {
                // We take the maximum, because otherwise we can get in trouble. Cron detects when a service expires,
                // removes it and recalculates players flags. Maximum timestamp is set again.
                // All in all, players flags table state is not entirely a reflection of reality.
                $flags[$flag] = $this->maxMinus(
                    array_get($flags, $flag),
                    $extraFlagUserService->getExpire()
                );
            }
        }

        if ($flags) {
            $this->playerFlagRepository->create($serverId, $type, $authData, $password, $flags);
        }
    }

    private function maxMinus($a, $b)
    {
        if ($a == -1 || $b == -1) {
            return -1;
        }

        return max($a, $b);
    }
}
