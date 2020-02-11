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
use App\Payment\PaymentModuleFactory;
use App\Repositories\GroupRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ServiceModule;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\DataField;
use App\View\Blocks\Block;
use App\View\Pages\Page;
use Exception;

class Heart
{
    public $pageTitle;

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

    private $servicesClasses = [];

    private $paymentModuleClasses = [];

    private $pagesClasses = [];
    private $blocksClasses = [];

    private $scripts = [];
    private $styles = [];

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

    /**
     * @param string $id
     * @param string $name
     * @param string $class
     *
     * @throws Exception
     */
    public function registerServiceModule($id, $name, $class)
    {
        if (isset($this->servicesClasses[$id])) {
            throw new InvalidConfigException("There is a service with such an id: [$id] already.");
        }

        $this->servicesClasses[$id] = [
            'name' => $name,
            'class' => $class,
        ];
    }

    /**
     * Get service module with service included
     *
     * @param string $serviceId Service identifier from ss_services
     * @return ServiceModule|null
     */
    public function getServiceModule($serviceId)
    {
        $service = $this->getService($serviceId);

        if (!$service) {
            return null;
        }

        if (!isset($this->servicesClasses[$service->getModule()])) {
            return null;
        }

        $className = $this->servicesClasses[$service->getModule()]['class'];

        return $className ? $this->app->makeWith($className, compact('service')) : null;
    }

    /**
     * Get service module without service included
     *
     * @param $moduleId
     * @return ServiceModule|null
     */
    public function getEmptyServiceModule($moduleId)
    {
        if (!isset($this->servicesClasses[$moduleId])) {
            return null;
        }

        if (!isset($this->servicesClasses[$moduleId]['class'])) {
            return null;
        }

        $classname = $this->servicesClasses[$moduleId]['class'];

        return $this->app->make($classname);
    }

    public function getServiceModuleName($moduleId)
    {
        if (!isset($this->servicesClasses[$moduleId])) {
            return null;
        }

        return $this->servicesClasses[$moduleId]['name'];
    }

    /**
     * @return ServiceModule[]
     */
    public function getEmptyServiceModules()
    {
        $modules = [];
        foreach (array_keys($this->servicesClasses) as $moduleId) {
            $modules[] = $this->getEmptyServiceModule($moduleId);
        }
        return $modules;
    }

    //
    // Klasy API płatności
    //

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
    // Obsługa bloków
    //

    /**
     * Rejestruje blok
     *
     * @param string $blockId
     * @param string $class
     *
     * @throws Exception
     */
    public function registerBlock($blockId, $class)
    {
        if ($this->blockExists($blockId)) {
            throw new InvalidConfigException(
                "There is a block with such an id: [$blockId] already."
            );
        }

        $this->blocksClasses[$blockId] = $class;
    }

    /**
     * Sprawdza czy dany blok istnieje
     *
     * @param string $blockId
     * @return bool
     */
    public function blockExists($blockId)
    {
        return isset($this->blocksClasses[$blockId]);
    }

    /**
     * Zwraca obiekt bloku
     *
     * @param string $blockId
     * @return Block|null
     */
    public function getBlock($blockId)
    {
        return $this->blockExists($blockId)
            ? $this->app->make($this->blocksClasses[$blockId])
            : null;
    }

    //
    // Obsługa stron
    //

    public function registerUserPage($pageId, $class)
    {
        $this->registerPage($pageId, $class, "user");
    }

    public function registerAdminPage($pageId, $class)
    {
        $this->registerPage($pageId, $class, "admin");
    }

    /**
     * Rejestruje strone
     *
     * @param string $pageId
     * @param string $class
     * @param string $type
     *
     * @throws Exception
     */
    private function registerPage($pageId, $class, $type)
    {
        if ($this->pageExists($pageId, $type)) {
            throw new InvalidConfigException("There is a page with such an id: [$pageId] already.");
        }

        $this->pagesClasses[$type][$pageId] = $class;
    }

    /**
     * Sprawdza czy dana strona istnieje
     *
     * @param string $pageId
     * @param string $type
     *
     * @return bool
     */
    public function pageExists($pageId, $type)
    {
        return isset($this->pagesClasses[$type][$pageId]);
    }

    /**
     * Zwraca obiekt strony
     *
     * @param string $pageId
     * @param string $type
     *
     * @return Page|null
     */
    public function getPage($pageId, $type = "user")
    {
        if ($this->pageExists($pageId, $type)) {
            $classname = $this->pagesClasses[$type][$pageId];
            return $this->app->make($classname);
        }

        return null;
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
     *
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

    public function canUserUseService($uid, Service $service)
    {
        $user = $this->getUser($uid);
        $combined = array_intersect($service->getGroups(), $user->getGroups());

        return empty($service->getGroups()) || !empty($combined);
    }

    //
    // SERVERS
    //

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
        // Wcześniej już pobraliśmy takiego użytkownika
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

    /**
     * Add JS script
     *
     * @param string $path
     */
    public function addScript($path)
    {
        if (!in_array($path, $this->scripts)) {
            $this->scripts[] = $path;
        }
    }

    /**
     * Add CSS stylesheet
     *
     * @param string $path
     */
    public function addStyle($path)
    {
        if (!in_array($path, $this->styles)) {
            $this->styles[] = $path;
        }
    }

    public function getScripts()
    {
        return collect($this->scripts)
            ->map(function ($path) {
                return "<script type=\"text/javascript\" src=\"{$path}\"></script>";
            })
            ->join("\n");
    }

    public function getStyles()
    {
        return collect($this->styles)
            ->map(function ($path) {
                return "<link href=\"{$path}\" rel=\"stylesheet\" />";
            })
            ->join("\n");
    }
}
