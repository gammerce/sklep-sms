<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Models\UserService;

class MybbUserService extends UserService
{
    /** @var int */
    private $mybbUid;

    public function __construct($id, $serviceId, $userId, $expire, $mybbUid)
    {
        parent::__construct($id, $serviceId, $userId, $expire);
        $this->mybbUid = $mybbUid;
    }

    /**
     * @return int
     */
    public function getMybbUid()
    {
        return $this->mybbUid;
    }
}
