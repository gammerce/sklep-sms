<?php
namespace App\Models;

class MybbUser
{
    /** @var int */
    private $uid;

    /** @var array */
    private $shop_groups = [];

    /** @var int */
    private $mybb_user_group;

    /** @var int[] */
    private $mybb_addgroups = [];

    /** @var  int */
    private $mybb_display_group;

    /**
     * @param int $uid
     * @param int $mybb_user_group
     */
    public function __construct($uid, $mybb_user_group)
    {
        $this->uid = intval($uid);
        $this->mybb_user_group = intval($mybb_user_group);
    }

    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int   $group_id
     * @param array $group
     */
    public function setShopGroup($group_id, $group)
    {
        if (!is_numeric($group_id)) {
            return;
        }

        $group['expire'] = intval($group['expire']);
        $this->shop_groups[intval($group_id)] = $group;

        // To nie jest grupa przydzielona przez MyBB, wiec usunmy ja stamtąd
        if (!$group['was_before']) {
            $this->removeMybbAddGroup($group_id);
        }
    }

    /**
     * @param integer $group_id
     * @param integer $seconds
     */
    public function prolongShopGroup($group_id, $seconds)
    {
        if (!is_numeric($group_id)) {
            return;
        }

        if (!isset($this->shop_groups[$group_id])) {
            $this->setShopGroup($group_id, [
                'expire'     => 0,
                'was_before' => in_array($group_id, $this->getMybbAddGroups()),
            ]);
        }

        $this->shop_groups[$group_id]['expire'] += intval($seconds);
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
            return $this->shop_groups;
        }

        return if_isset($this->shop_groups[$key], null);
    }

    /**
     * @param int|null $group_id
     */
    public function removeShopGroup($group_id = null)
    {
        if ($group_id === null) {
            $this->shop_groups = [];
        } else {
            unset($this->shop_groups[$group_id]);
        }
    }

    /**
     * @return array
     */
    public function getMybbAddGroups()
    {
        return $this->mybb_addgroups;
    }

    /**
     * @param int[] $groups
     */
    public function setMybbAddGroups($groups)
    {
        foreach ($groups as $group_id) {
            if (!is_numeric($group_id)) {
                continue;
            }

            if (isset($this->shop_groups[intval($group_id)]) && !$this->shop_groups[intval($group_id)]['was_before']) {
                continue;
            }

            $this->mybb_addgroups[] = intval($group_id);
        }
    }

    /**
     * @param int $group_id
     */
    public function removeMybbAddGroup($group_id)
    {
        if (($key = array_search($group_id, $this->mybb_addgroups)) !== false) {
            unset($this->mybb_addgroups[$key]);
        }
    }

    /**
     * @return int
     */
    public function getMybbUserGroup()
    {
        return $this->mybb_user_group;
    }

    /**
     * @return int
     */
    public function getMybbDisplayGroup()
    {
        return $this->mybb_display_group;
    }

    /**
     * @param int $mybb_display_group
     */
    public function setMybbDisplayGroup($mybb_display_group)
    {
        $this->mybb_display_group = intval($mybb_display_group);
    }
}