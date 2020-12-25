<?php
namespace App\ServiceModules\ExtraFlags;

use App\Managers\ServiceManager;
use App\Repositories\UserServiceRepository;
use App\Service\ExpiredUserServiceService;
use App\Support\Database;
use App\Support\Expression;

class PlayerFlagService
{
    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    /** @var Database */
    private $db;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var ExpiredUserServiceService */
    private $expiredUserServiceService;

    public function __construct(
        PlayerFlagRepository $playerFlagRepository,
        ExtraFlagUserServiceRepository $extraFlagUserServiceRepository,
        UserServiceRepository $userServiceRepository,
        ExpiredUserServiceService $expiredUserServiceService,
        Database $db,
        ServiceManager $serviceManager
    ) {
        $this->playerFlagRepository = $playerFlagRepository;
        $this->db = $db;
        $this->serviceManager = $serviceManager;
        $this->extraFlagUserServiceRepository = $extraFlagUserServiceRepository;
        $this->userServiceRepository = $userServiceRepository;
        $this->expiredUserServiceService = $expiredUserServiceService;
    }

    /**
     * @param string $serviceId
     * @param number $serverId
     * @param number|null $days
     * @param string $type
     * @param string $authData
     * @param string|null $password
     * @param number|null $userId
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
        $statement = $this->db->statement(
            <<<EOF
SELECT * FROM `ss_user_service` AS us 
INNER JOIN `$table` AS m ON m.us_id = us.id 
WHERE us.`service_id` = ? AND m.`server_id` = ? AND m.`type` = ? AND m.`auth_data` = ?
EOF
        );
        $statement->execute([$serviceId, $serverId, $type, $authData]);

        if ($statement->rowCount()) {
            $userService = $this->extraFlagUserServiceRepository->mapToModel($statement->fetch());

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
        $this->db
            ->statement(
                "UPDATE `$table` " .
                    "SET `password` = ? " .
                    "WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?"
            )
            ->execute([$password, $serverId, $type, $authData]);

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
        // Musi byc podany typ, bo inaczej nam wywali wszystkie usługi bez typu
        // Bez serwera oraz auth_data, skrypt po prostu nic nie zrobi
        if (!$type) {
            return;
        }

        // Usuwanie dane, ponieważ za chwilę będziemy je tworzyć na nowo
        $this->playerFlagRepository->deleteByCredentials($serverId, $type, $authData);

        // Pobieranie wszystkich usług na konkretne dane
        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS usef ON us.id = usef.us_id " .
                "WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ? AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )"
        );
        $statement->execute([$serverId, $type, $authData]);

        // Wyliczanie za jaki czas dana flaga ma wygasnąć
        $flags = [];
        $password = "";
        foreach ($statement as $row) {
            $extraFlagUserService = $this->extraFlagUserServiceRepository->mapToModel($row);

            // Pobranie hasła, bierzemy je tylko raz na początku
            $password = $password ? $password : $extraFlagUserService->getPassword();

            $service = $this->serviceManager->get($extraFlagUserService->getServiceId());
            $serviceFlags = $service->getFlags();
            foreach (str_split($serviceFlags) as $flag) {
                // Bierzemy maksa, ponieważ inaczej robią się problemy.
                // A tak to jak wygaśnie jakaś usługa, to wykona się cron, usunie ją i przeliczy flagi jeszcze raz
                // I znowu weźmie maksa
                // Czyli stan w tabeli players flags nie jest do końca odzwierciedleniem rzeczywistości :)
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
