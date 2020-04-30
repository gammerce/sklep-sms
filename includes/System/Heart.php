<?php
namespace App\System;

use App\Models\Group;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\Service;
use App\Models\User;
use App\Repositories\GroupRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

class Heart
{
    /** @var UserRepository */
    private $userRepository;

    /** @var ServiceRepository */
    private $serviceRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var ServerServiceRepository */
    private $serverServiceRepository;

    /** @var Server[] */
    private $servers = [];
    private $serversFetched = false;

    /** @var Group[] */
    private $groups = [];
    private $groupsFetched = false;

    /** @var Service[] */
    private $services = [];
    private $servicesFetched = false;

    /** @var ServerService[] */
    private $serversServices = [];
    private $serversServicesFetched = false;

    /** @var User[] */
    private $users = [];

    public function __construct(
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        ServerRepository $serverRepository,
        GroupRepository $groupRepository,
        ServerServiceRepository $serverServiceRepository
    ) {
        $this->userRepository = $userRepository;
        $this->serviceRepository = $serviceRepository;
        $this->serverRepository = $serverRepository;
        $this->groupRepository = $groupRepository;
        $this->serverServiceRepository = $serverServiceRepository;
    }

    /**
     * Returns purchasable services
     *
     * @return Service[]
     */
    public function getServices()
    {
        if (!$this->servicesFetched) {
            $this->fetchServices();
        }

        return $this->services;
    }

    /**
     * @param $serviceId
     * @return Service|null
     */
    public function getService($serviceId)
    {
        if (!$this->servicesFetched) {
            $this->fetchServices();
        }

        return array_get($this->services, $serviceId, null);
    }

    private function fetchServices()
    {
        foreach ($this->serviceRepository->all() as $service) {
            $this->services[$service->getId()] = $service;
        }

        $this->servicesFetched = true;
    }

    /**
     * @return Server[]
     */
    public function getServers()
    {
        if (!$this->serversFetched) {
            $this->fetchServers();
        }

        return $this->servers;
    }

    /**
     * @param int $id
     * @return Server|null
     */
    public function getServer($id)
    {
        return array_get($this->getServers(), $id, null);
    }

    public function getServersAmount()
    {
        return count($this->getServers());
    }

    private function fetchServers()
    {
        foreach ($this->serverRepository->all() as $server) {
            $this->servers[$server->getId()] = $server;
        }

        $this->serversFetched = true;
    }

    /**
     * Checks if the service can be purchased on the given server
     *
     * @param int $serverId
     * @param string $serviceId
     *
     * @return boolean
     */
    public function serverServiceLinked($serverId, $serviceId)
    {
        if (!$this->serversServicesFetched) {
            $this->fetchServersServices();
        }

        return isset($this->serversServices[$serverId][$serviceId]);
    }

    private function fetchServersServices()
    {
        foreach ($this->serverServiceRepository->all() as $serverService) {
            $this->serversServices[$serverService->getServerId()][
                $serverService->getServiceId()
            ] = true;
        }

        $this->serversServicesFetched = true;
    }

    /**
     * @param int $uid
     * @return User
     */
    public function getUser($uid)
    {
        if ($uid && isset($this->users[$uid])) {
            return $this->users[$uid];
        }

        $user = $this->userRepository->get($uid);

        if ($user) {
            $this->users[$user->getUid()] = $user;
            return $user;
        }

        return new User();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->users[$user->getUid()] = $user;
    }

    /**
     * @param string $login
     * @param string $password
     * @return User|null
     */
    public function getUserByLogin($login, $password)
    {
        $user = $this->userRepository->findByPassword($login, $password);

        if ($user) {
            $this->users[$user->getUid()] = $user;
            return $user;
        }

        return null;
    }

    /**
     * @return Group[]
     */
    public function getGroups()
    {
        if (!$this->groupsFetched) {
            $this->fetchGroups();
        }

        return $this->groups;
    }

    /**
     * @param $id
     * @return Group|null
     */
    public function getGroup($id)
    {
        if (!$this->groupsFetched) {
            $this->fetchGroups();
        }

        return array_get($this->groups, $id, null);
    }

    private function fetchGroups()
    {
        foreach ($this->groupRepository->all() as $group) {
            $this->groups[$group->getId()] = $group;
        }

        $this->groupsFetched = true;
    }
}
