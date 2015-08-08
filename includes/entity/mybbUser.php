<?php

class Entity_MyBB_User {

	/**
	 * @var integer
	 */
	private $uid;

	/**
	 * @var array
	 */
	private $groups = array();

	/**
	 * @var int[]
	 */
	private $mybb_addgroups = array();

	/**
	 * @param int $uid
	 */
	function __contruct($uid) {
		$this->uid = $uid;
	}

	/**
	 * @param array $groups
	 */
	public function setGroups($groups) {
		foreach($groups as $key => $value) {
			if (intval($key) != 0)
				$this->groups[intval($key)] = intval($value);
		}
	}

	/**
	 * @param integer $group
	 * @param integer $seconds
	 */
	public function prolongGroup($group, $seconds) {
		if (intval($group) == 0)
			return;

		if (!isset($this->groups[$group]))
			$this->groups[$group] = 0;

		$this->groups[$group] += intval($seconds);
	}

	public function getUid() {
		return $this->uid;
	}

	public function getGroups($key = NULL) {
		if ($key === NULL)
			return $this->groups;

		return if_isset($this->groups[$key], NULL);
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