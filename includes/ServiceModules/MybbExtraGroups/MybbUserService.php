<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Models\UserService;

class MybbUserService extends UserService
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
