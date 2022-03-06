<?php
namespace App\License\ServiceModules\ShopSmsLicense;

use App\License\Models\LicenseUserService;
use App\Repositories\UserServiceRepository;
use App\Server\Platform;
use App\Support\Database;

class LicenseUserServiceRepository
{
    private Database $db;
    private UserServiceRepository $userServiceRepository;

    public function __construct(Database $db, UserServiceRepository $userServiceRepository)
    {
        $this->db = $db;
        $this->userServiceRepository = $userServiceRepository;
    }

    /**
     * @param string $serviceId
     * @param int $expiresAt
     * @param int|null $userId
     * @param string|null $comment
     * @param string $identifier
     * @param int $costDaily
     * @param string $email
     * @param Platform[] $platforms
     * @param string|null $subdomain
     * @return LicenseUserService
     */
    public function create(
        $serviceId,
        $expiresAt,
        $userId,
        $comment,
        $identifier,
        $costDaily,
        $email,
        array $platforms,
        $subdomain
    ): LicenseUserService {
        $userServiceId = $this->userServiceRepository->createFixedExpire(
            $serviceId,
            $expiresAt,
            $userId,
            $comment
        );

        $table = LicenseServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            <<<EOF
INSERT INTO `$table` SET 
`us_id` = ?, 
`service_id` = ?, 
`identifier` = ?, 
`cost_daily` = ?, 
`email` = ?, 
`platform_amxmodx` = ?, 
`platform_sourcemod` = ?,
`subdomain` = ?
EOF
        );
        $statement->execute([
            $userServiceId,
            $serviceId,
            $identifier,
            $costDaily,
            $email,
            (int) in_array(Platform::AMXMODX(), $platforms),
            (int) in_array(Platform::SOURCEMOD(), $platforms),
            $subdomain,
        ]);

        return $this->get($userServiceId);
    }

    /**
     * @return LicenseUserService[]
     */
    public function all(): array
    {
        $table = LicenseServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            <<<EOF
                SELECT * FROM `ss_user_service` AS us 
                INNER JOIN `$table` AS m ON m.us_id = us.id 
EOF
        );

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function get($id): ?LicenseUserService
    {
        if ($id) {
            $table = LicenseServiceModule::USER_SERVICE_TABLE;
            $statement = $this->db->statement(
                <<<EOF
                SELECT * FROM `ss_user_service` AS us 
                INNER JOIN `$table` AS m ON m.us_id = us.id 
                WHERE `id` = ?
EOF
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findBySubdomain(string $subdomain): ?LicenseUserService
    {
        $table = LicenseServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                "WHERE m.subdomain = ?"
        );
        $statement->execute([$subdomain]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    public function mapToModel(array $data): LicenseUserService
    {
        return new LicenseUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_string($data["comment"]),
            as_string($data["identifier"]),
            as_string($data["email"]),
            as_int($data["cost_daily"]),
            (bool) $data["platform_amxmodx"],
            (bool) $data["platform_sourcemod"],
            as_string($data["subdomain"])
        );
    }
}
