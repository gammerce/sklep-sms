<?php
namespace App\Models;

class MybbExtraGroupsUserService extends UserService
{
    /** @var int */
    private $mybbUid;

    public function __construct($id, $serviceId, $uid, $expire, $mybbUid)
    {
        parent::__construct($id, $serviceId, $uid, $expire);

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
