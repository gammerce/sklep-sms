<?php

class Entity_MyBB_User {

	/**
	 * @var int
	 */
	private $uid;

	/**
	 * @var array
	 */
	private $shop_groups = array();

	/**
	 * @var int[]
	 */
	private $mybb_addgroups = array();

	/**
	 * @param int $uid
	 */
	function __construct($uid) {
		$this->uid = intval($uid);
	}

	/**
	 * @param array $groups
	 */
	public function setShopGroups($groups) {
		foreach($groups as $key => $value) {
			if (intval($key) != 0)
				$this->shop_groups[intval($key)] = intval($value);
		}
	}

	/**
	 * @param integer $group
	 * @param integer $seconds
	 */
	public function prolongShopGroup($group, $seconds) {
		if (intval($group) == 0)
			return;

		if (!isset($this->shop_groups[$group]))
			$this->shop_groups[$group] = 0;

		$this->shop_groups[$group] += intval($seconds);
	}

	public function getUid() {
		return $this->uid;
	}

	public function getShopGroups($key = NULL) {
		if ($key === NULL)
			return $this->shop_groups;

		return if_isset($this->shop_groups[$key], NULL);
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
		foreach($groups as $group) {
			if (intval($group) != 0)
				$this->mybb_addgroups[] = intval($group);
		}
	}

}