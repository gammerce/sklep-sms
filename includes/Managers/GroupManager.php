<?php
namespace App\Managers;

use App\Models\Group;
use App\Repositories\GroupRepository;

class GroupManager
{
    private GroupRepository $groupRepository;

    /** @var Group[] */
    private array $groups = [];
    private bool $groupsFetched = false;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * @return Group[]
     */
    public function all()
    {
        if (!$this->groupsFetched) {
            $this->fetch();
        }

        return $this->groups;
    }

    /**
     * @param $id
     * @return Group|null
     */
    public function get($id)
    {
        if (!$this->groupsFetched) {
            $this->fetch();
        }

        return array_get($this->groups, $id, null);
    }

    private function fetch()
    {
        foreach ($this->groupRepository->all() as $group) {
            $this->groups[$group->getId()] = $group;
        }

        $this->groupsFetched = true;
    }
}
