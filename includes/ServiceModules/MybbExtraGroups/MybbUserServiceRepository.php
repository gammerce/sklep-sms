<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Repositories\UserServiceRepository;
use App\Support\Database;
use App\Support\Expression;

class MybbUserServiceRepository
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
     * @param int $userId
     * @param int|null $seconds
     * @param int $mybbUid
     * @return MybbUserService|null
     */
    public function create($serviceId, $userId, $seconds, $mybbUid)
    {
        $table = MybbExtraGroupsServiceModule::USER_SERVICE_TABLE;

        // Dodajemy usługę gracza do listy usług
        // Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
        $statement = $this->db->statement(
            "SELECT `us_id` FROM `{$table}` WHERE `service_id` = ? AND `mybb_uid` = ?"
        );
        $statement->execute([$serviceId, $mybbUid]);

        if ($statement->rowCount()) {
            $row = $statement->fetch();
            $userServiceId = $row["us_id"];

            $this->userServiceRepository->updateWithModule($table, $userServiceId, [
                "user_id" => $userId,
                "mybb_uid" => $mybbUid,
                "expire" => $seconds === null ? null : new Expression("`expire` + $seconds"),
            ]);
        } else {
            $userServiceId = $this->userServiceRepository->create($serviceId, $seconds, $userId);

            $this->db
                ->statement(
                    "INSERT INTO `{$table}` (`us_id`, `service_id`, `mybb_uid`) VALUES (?, ?, ?)"
                )
                ->execute([$userServiceId, $serviceId, $mybbUid]);
        }

        return $this->get($userServiceId);
    }

    /**
     * @param $id
     * @return MybbUserService|null
     */
    public function get($id)
    {
        if ($id) {
            $table = MybbExtraGroupsServiceModule::USER_SERVICE_TABLE;
            $statement = $this->db->statement(
                "SELECT * FROM `ss_user_service` AS us " .
                    "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                    "WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param array $data
     * @return MybbUserService
     */
    public function mapToModel(array $data)
    {
        return new MybbUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_int($data["mybb_uid"])
        );
    }
}
