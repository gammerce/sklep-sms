<?php
namespace App\System;

use App\Exceptions\InvalidConfigException;
use App\Exceptions\InvalidPaymentModuleException;
use App\Models\Group;
use App\Models\PaymentPlatform;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\Service;
use App\Models\User;
use App\Payment\General\PaymentModuleFactory;
use App\Repositories\GroupRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\DataField;

class Heart
{
    /** @var Application */
    private $app;

    /** @var UserRepository */
    private $userRepository;

    /** @var ServiceRepository */
    private $serviceRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var ServerServiceRepository */
    private $serverServiceRepository;

    /** @var PaymentModuleFactory */
    private $paymentModuleFactory;

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

    private $paymentModuleClasses = [];

    public function __construct(
        Application $app,
        UserRepository $userRepository,
        ServiceRepository $serviceRepository,
        ServerRepository $serverRepository,
        GroupRepository $groupRepository,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServerServiceRepository $serverServiceRepository,
        PaymentModuleFactory $paymentModuleFactory
    ) {
        $this->userRepository = $userRepository;
        $this->serviceRepository = $serviceRepository;
        $this->serverRepository = $serverRepository;
        $this->groupRepository = $groupRepository;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->paymentModuleFactory = $paymentModuleFactory;
        $this->app = $app;
        $this->serverServiceRepository = $serverServiceRepository;
    }

    public function registerPaymentModule($moduleId, $class)
    {
        if (isset($this->paymentModuleClasses[$moduleId])) {
            throw new InvalidConfigException(
                "There is a payment api with id: [$moduleId] already."
            );
        }

        $this->paymentModuleClasses[$moduleId] = $class;
    }

    public function getPaymentModuleIds()
    {
        return array_keys($this->paymentModuleClasses);
    }

    /**
     * @param string $moduleId
     * @return DataField[]
     */
    public function getPaymentModuleDataFields($moduleId)
    {
        $className = array_get($this->paymentModuleClasses, $moduleId);

        if ($className) {
            return $className::getDataFields();
        }

        throw new InvalidPaymentModuleException();
    }

    /**
     * @param PaymentPlatform $paymentPlatform
     * @return PaymentModule|null
     */
    public function getPaymentModule(PaymentPlatform $paymentPlatform)
    {
        $paymentModuleClass = array_get(
            $this->paymentModuleClasses,
            $paymentPlatform->getModuleId()
        );

        if ($paymentModuleClass) {
            return $this->paymentModuleFactory->create($paymentModuleClass, $paymentPlatform);
        }

        return null;
    }

    /**
     * @param string $platformId
     * @return PaymentModule|null
     */
    public function getPaymentModuleByPlatformId($platformId)
    {
        $paymentPlatform = $this->paymentPlatformRepository->get($platformId);
        if (!$paymentPlatform) {
            return null;
        }

        $paymentModule = $this->getPaymentModule($paymentPlatform);
        if (!$paymentModule) {
            return null;
        }

        return $paymentModule;
    }

    //
    // SERVICES
    //

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

    //
    // Servers - Services
    //

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

    //
    // Users
    //

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
     * @param string $login
     * @param string $password
     * @return User
     */
    public function getUserByLogin($login, $password)
    {
        $user = $this->userRepository->findByPassword($login, $password);

        if ($user) {
            $this->users[$user->getUid()] = $user;
            return $user;
        }

        return new User();
    }

    //
    // Groups
    //

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
