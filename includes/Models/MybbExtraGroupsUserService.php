<?php
namespace App\Models;

class MybbExtraGroupsUserService extends UserService
{
    private int $mybbUid;

    public function __construct($id, $serviceId, $userId, $expire, $mybbUid)
    {
        parent::__construct($id, $serviceId, $userId, $expire);
        $this->mybbUid = $mybbUid;
    }

    public function getMybbUid(): int
    {
        return $this->mybbUid;
    }
}
