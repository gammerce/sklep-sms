<?php
namespace App\ServiceModules\ExtraFlags;

use App\Managers\ServiceManager;
use App\Support\Database;

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

    public function __construct(
        PlayerFlagRepository $playerFlagRepository,
        ExtraFlagUserServiceRepository $extraFlagUserServiceRepository,
        Database $db,
        ServiceManager $serviceManager
    ) {
        $this->playerFlagRepository = $playerFlagRepository;
        $this->db = $db;
        $this->serviceManager = $serviceManager;
        $this->extraFlagUserServiceRepository = $extraFlagUserServiceRepository;
    }

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
                "WHERE `server` = ? AND `type` = ? AND `auth_data` = ? AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )"
        );
        $statement->execute([$serverId, $type, $authData]);

        // Wyliczanie za jaki czas dana flaga ma wygasnąć
        $flags = [];
        $password = "";
        foreach ($statement as $row) {
            $extraFlagUserService = $this->extraFlagUserServiceRepository->mapToModel($row);

            // Pobranie hasła, bierzemy je tylko raz na początku
            $password = $password ? $password : $extraFlagUserService->getPassword();

            $service = $this->serviceManager->getService($extraFlagUserService->getServiceId());
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
