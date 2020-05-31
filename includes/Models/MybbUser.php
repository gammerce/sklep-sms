<?php
namespace App\Models;

class MybbUser
{
    /** @var int */
    private $uid;

    /** @var array */
    private $shopGroups = [];

    /** @var int */
    private $mybbUserGroup;

    /** @var int[] */
    private $mybbAddGroups = [];

    /** @var int */
    private $mybbDisplayGroup;

    /**
     * @param int $uid
     * @param int $mybbUserGroup
     */
    public function __construct($uid, $mybbUserGroup)
    {
        $this->uid = (int) $uid;
        $this->mybbUserGroup = (int) $mybbUserGroup;
    }

    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int   $groupId
     * @param array $group
     */
    public function setShopGroup($groupId, $group)
    {
        if (!is_numeric($groupId)) {
            return;
        }

        $group["expire"] = as_int($group["expire"]);
        $this->shopGroups[(int) $groupId] = $group;

        // To nie jest grupa przydzielona przez MyBB, wiec usunmy ja stamtąd
        if (!$group["was_before"]) {
            $this->removeMybbAddGroup($groupId);
        }
    }

    /**
     * @param int $groupId
     * @param int|null $seconds
     */
    public function prolongShopGroup($groupId, $seconds)
    {
        if (!is_numeric($groupId)) {
            return;
        }

        if (!isset($this->shopGroups[$groupId])) {
            $this->setShopGroup($groupId, [
                "expire" => 0,
                "was_before" => in_array($groupId, $this->getMybbAddGroups()),
            ]);
        }

        if ($seconds === null) {
            $this->shopGroups[$groupId]["expire"] = null;
        } else {
            $this->shopGroups[$groupId]["expire"] += (int) $seconds;
        }
    }

    /**
     * @param int|null $key
     *
     * @return array
     *  int expire
     *  bool was_before
     */
    public function getShopGroup($key = null)
    {
        if ($key === null) {
            return $this->shopGroups;
        }

        return array_get($this->shopGroups, $key);
    }

    /**
     * @param int|null $groupId
     */
    public function removeShopGroup($groupId = null)
    {
        if ($groupId === null) {
            $this->shopGroups = [];
        } else {
            unset($this->shopGroups[$groupId]);
        }
    }

    /**
     * @return array
     */
    public function getMybbAddGroups()
    {
        return $this->mybbAddGroups;
    }

    /**
     * @param int[] $groups
     */
    public function setMybbAddGroups($groups)
    {
        foreach ($groups as $groupId) {
            if (!is_numeric($groupId)) {
                continue;
            }

            if (
                isset($this->shopGroups[(int) $groupId]) &&
                !$this->shopGroups[(int) $groupId]["was_before"]
            ) {
                continue;
            }

            $this->mybbAddGroups[] = (int) $groupId;
        }
    }

    /**
     * @param int $groupId
     */
    public function removeMybbAddGroup($groupId)
    {
        if (($key = array_search($groupId, $this->mybbAddGroups)) !== false) {
            unset($this->mybbAddGroups[$key]);
        }
    }

    /**
     * @return int
     */
    public function getMybbUserGroup()
    {
        return $this->mybbUserGroup;
    }

    /**
     * @return int
     */
    public function getMybbDisplayGroup()
    {
        return $this->mybbDisplayGroup;
    }

    /**
     * @param int $mybbDisplayGroup
     */
    public function setMybbDisplayGroup($mybbDisplayGroup)
    {
        $this->mybbDisplayGroup = (int) $mybbDisplayGroup;
    }
}
