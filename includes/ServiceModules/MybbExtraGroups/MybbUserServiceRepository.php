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
     * @param string|null $comment
     * @return MybbUserService
     */
    public function create($serviceId, $userId, $seconds, $mybbUid, $comment): MybbUserService
    {
        $table = MybbExtraGroupsServiceModule::USER_SERVICE_TABLE;
        $userService = $this->findByServiceIdAndMybbUid($serviceId, $mybbUid);

        if ($userService) {
            $this->userServiceRepository->updateWithModule($table, $userService->getId(), [
                "user_id" => $userId,
                "mybb_uid" => $mybbUid,
                "expire" => $seconds === null ? null : new Expression("`expire` + $seconds"),
                "comment" => trim($userService->getComment() . "\n---\n" . $comment),
            ]);

            return $userService;
        }

        $userServiceId = $this->userServiceRepository->create(
            $serviceId,
            $seconds,
            $userId,
            $comment
        );
        $this->db
            ->statement(
                "INSERT INTO `{$table}` (`us_id`, `service_id`, `mybb_uid`) VALUES (?, ?, ?)"
            )
            ->execute([$userServiceId, $serviceId, $mybbUid]);

        return $this->get($userServiceId);
    }

    /**
     * @param $id
     * @return MybbUserService|null
     */
    public function get($id): ?MybbUserService
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

    public function findByServiceIdAndMybbUid($serviceId, $mybbUid): ?MybbUserService
    {
        $table = MybbExtraGroupsServiceModule::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "SELECT `us_id` FROM `{$table}` WHERE `service_id` = ? AND `mybb_uid` = ?"
        );
        $statement->execute([$serviceId, $mybbUid]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    public function mapToModel(array $data): MybbUserService
    {
        return new MybbUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_int($data["comment"]),
            as_int($data["mybb_uid"])
        );
    }
}
