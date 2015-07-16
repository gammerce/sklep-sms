<?php

class Entity_MyBB_User {

	/**
	 * @var integer
	 */
	private $uid= NULL;

	/**
	 * @var array
	 */
	private $groups = NULL;

	function __contruct($data) {
		if (isset($data['uid']))
			$this->setUid($data['uid']);

		if (isset($data['groups']))
			$this->setGroups($data['groups']);
	}

	public function setUid($uid) {
		$this->uid = (integer)$uid;
	}

	/**
	 * @param array $groups
	 */
	public function setGroups($groups) {
		foreach($groups as $key => $value)
			$this->groups[$key] = (integer)$value;
	}

	/**
	 * @param integer $group
	 * @param integer $seconds
	 */
	public function prolongGroup($group, $seconds) {
		if (!isset($this->groups[$group]))
			$this->groups[$group] = 0;

		$this->groups[$group] += (integer)$seconds;
	}

	public function getUid() {
		return $this->uid;
	}

	public function getGroup($key = NULL) {
		if ($key === NULL)
			return $this->groups;

		return if_isset($this->groups[$key], NULL);
	}

}