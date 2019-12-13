<?php
namespace App\System;

use App\Blocks\Block;
use App\Blocks\BlockSimple;
use App\Exceptions\InvalidConfigException;
use App\Models\Tariff;
use App\Models\User;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Pages\Page;
use App\Pages\PageSimple;
use App\Repositories\UserRepository;
use App\Services\ChargeWallet\ServiceChargeWallet;
use App\Services\ExtraFlags\ServiceExtraFlags;
use App\Services\Other\ServiceOther;
use App\Services\Service;
use App\Verification\Abstracts\PaymentModule;
use Exception;

class Heart
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var Template */
    private $template;

    /** @var UserRepository */
    private $userRepository;

    private $servers = [];

    private $serversFetched = false;
    private $services = [];

    private $servicesFetched = false;
    private $serversServices = [];

    private $serversServicesFetched = false;

    /** @var Tariff[] */
    private $tariffs = [];
    private $tariffsFetched = false;
    public $pageTitle;
    private $servicesClasses = [];

    private $paymentModuleClasses = [];

    private $pagesClasses = [];
    private $blocksClasses = [];

    /** @var User[] */
    private $users = [];
    private $groups = [];
    private $groupsFetched = false;
    private $scripts = [];
    private $styles = [];

    public function __construct(
        Database $db,
        Settings $settings,
        Template $template,
        UserRepository $userRepository
    ) {
        $this->db = $db;
        $this->settings = $settings;
        $this->template = $template;
        $this->userRepository = $userRepository;
    }

    /**
     * @param string $id
     * @param string $name
     * @param string $class
     * @param string $classsimple
     *
     * @throws Exception
     */
    public function registerServiceModule($id, $name, $class, $classsimple)
    {
        if (isset($this->servicesClasses[$id])) {
            throw new Exception(
                "There is a service with such an id: " . htmlspecialchars($id) . " already."
            );
        }

        $this->servicesClasses[$id] = [
            'name' => $name,
            'class' => $class,
            'classsimple' => $classsimple,
        ];
    }

    /**
     * Zwraca obiekt modułu usługi
     * Moduł jest wypełniony, są w nim wszystkie dane
     *
     * @param $serviceId
     *
     * @return null|Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther
     */
    public function getServiceModule($serviceId)
    {
        // Brak usługi o takim ID
        if (($service = $this->getService($serviceId)) === null) {
            return null;
        }

        // Brak takiego modułu
        if (!isset($this->servicesClasses[$service['module']])) {
            return null;
        }

        $className = $this->servicesClasses[$service['module']]['class'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return strlen($className) ? app()->makeWith($className, ['service' => $service]) : null;
    }

    /**
     * Funkcja zwraca klasę modułu przez jego id
     * Moduł jest pusty, nie ma danych o usłudze
     * s - simple
     *
     * @param $moduleId
     * @return Service|null
     */
    public function getServiceModuleS($moduleId)
    {
        // Brak takiego modułu
        if (!isset($this->servicesClasses[$moduleId])) {
            return null;
        }

        if (!isset($this->servicesClasses[$moduleId]['classsimple'])) {
            return null;
        }

        $classname = $this->servicesClasses[$moduleId]['classsimple'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return app()->make($classname);
    }

    public function getServiceModuleName($moduleId)
    {
        // Brak takiego modułu
        if (!isset($this->servicesClasses[$moduleId])) {
            return null;
        }

        return $this->servicesClasses[$moduleId]['name'];
    }

    /**
     * Zwraca wszystkie zarejestrowane moduły usług
     *
     * @return array
     */
    public function getServicesModules()
    {
        $modules = [];
        foreach ($this->servicesClasses as $id => $data) {
            $modules[] = [
                'id' => $id,
                'name' => $data['name'],
                'class' => $data['class'],
                'classsimple' => $data['classsimple'],
            ];
        }

        return $modules;
    }

    //
    // Klasy API płatności
    //

    public function registerPaymentModule($id, $class)
    {
        if (isset($this->paymentModuleClasses[$id])) {
            throw new Exception(
                "There is a payment api with id: " . htmlspecialchars($id) . " already."
            );
        }

        $this->paymentModuleClasses[$id] = $class;
    }

    /**
     * @param string $id
     * @return PaymentModule|null
     */
    public function getPaymentModule($id)
    {
        if (isset($this->paymentModuleClasses[$id])) {
            return app()->make($this->paymentModuleClasses[$id]);
        }

        return null;
    }

    /**
     * @param string $id
     * @return PaymentModule
     */
    public function getPaymentModuleOrFail($id)
    {
        $paymentModule = $this->getPaymentModule($id);

        if ($paymentModule) {
            return $paymentModule;
        }

        throw new InvalidConfigException("Invalid payment module [$id].");
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
            throw new Exception(
                "There is a block with such an id: " . htmlspecialchars($blockId) . " already."
            );
        }

        $this->blocksClasses[$blockId] = $class;
    }

    /**
     * Sprawdza czy dany blok istnieje
     *
     * @param string $blockId
     *
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
     *
     * @return null|Block|BlockSimple
     */
    public function getBlock($blockId)
    {
        return $this->blockExists($blockId) ? app()->make($this->blocksClasses[$blockId]) : null;
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
            throw new Exception(
                "There is a page with such an id: " . htmlspecialchars($pageId) . " already."
            );
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
    public function pageExists($pageId, $type = "user")
    {
        return isset($this->pagesClasses[$type][$pageId]);
    }

    /**
     * Zwraca obiekt strony
     *
     * @param string $pageId
     * @param string $type
     *
     * @return null|Page|PageSimple|IPageAdminActionBox
     */
    public function getPage($pageId, $type = "user")
    {
        if (!$this->pageExists($pageId, $type)) {
            return null;
        }

        $classname = $this->pagesClasses[$type][$pageId];

        return app()->make($classname);
    }

    //
    // SERVICES
    //

    /**
     * Zwraca wszystkie stworzone usługi do zakupienia
     *
     * @return array
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
     * @return mixed
     */
    public function getService($serviceId)
    {
        if (!$this->servicesFetched) {
            $this->fetchServices();
        }

        return if_isset($this->services[$serviceId], null);
    }

    /**
     * Number of purchasable services
     *
     * @return int
     */
    public function getServicesAmount()
    {
        return count($this->services);
    }

    private function fetchServices()
    {
        $result = $this->db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "services` " . "ORDER BY `order` ASC"
        );
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $row['id_hsafe'] = htmlspecialchars($row['id']);
            $row['name'] = htmlspecialchars($row['name']);
            $row['groups'] = $row['groups'] ? explode(";", $row['groups']) : [];
            $row['data'] = json_decode($row['data'], true);
            $this->services[$row['id']] = $row;
        }
        $this->servicesFetched = true;
    }

    public function userCanUseService($uid, $service)
    {
        $user = $this->getUser($uid);
        $combined = array_intersect($service['groups'], $user->getGroups());

        return empty($service['groups']) || !empty($combined);
    }

    //
    // SERVERS
    //

    public function getServers()
    {
        if (!$this->serversFetched) {
            $this->fetchServers();
        }

        return $this->servers;
    }

    public function getServer($id)
    {
        if (!$this->serversFetched) {
            $this->fetchServers();
        }

        return if_isset($this->servers[$id], null);
    }

    public function getServersAmount()
    {
        return count($this->servers);
    }

    private function fetchServers()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers`");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->servers[$row['id']] = $row;
        }
        $this->serversFetched = true;
    }

    //
    // Servers - Services
    //

    /**
     * Checks if the service can be purchased on the given server
     *
     * @param integer $serverId
     * @param string  $serviceId
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
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers_services`");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $this->serversServices[$row['server_id']][$row['service_id']] = true;
        }
        $this->serversServicesFetched = true;
    }

    //
    // TARRIFS
    //

    /**
     * @return Tariff[]
     */
    public function getTariffs()
    {
        if (!$this->tariffsFetched) {
            $this->fetchTariffs();
        }

        return $this->tariffs;
    }

    /**
     * @param int $id
     *
     * @return Tariff | null
     */
    public function getTariff($id)
    {
        if (!$this->tariffsFetched) {
            $this->fetchTariffs();
        }

        return if_isset($this->tariffs[$id], null);
    }

    public function getTariffsAmount()
    {
        return count($this->tariffs);
    }

    private function fetchTariffs()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "tariffs`");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $this->tariffs[$row['id']] = new Tariff(
                $row['id'],
                $row['provision'],
                $row['predefined']
            );
        }

        $this->tariffsFetched = true;
    }

    //
    // Users
    //

    /**
     * @param int    $uid
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
     * @param string$password
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

    public function hasUserGroup($uid, $gid)
    {
        $user = $this->getUser($uid);

        return in_array($gid, $user->getGroups());
    }

    //
    // Groups
    //

    public function getGroups()
    {
        if (!$this->groupsFetched) {
            $this->fetchGroups();
        }

        return $this->groups;
    }

    public function getGroup($id)
    {
        if (!$this->groupsFetched) {
            $this->fetchGroups();
        }

        return if_isset($this->groups[$id], null);
    }

    public function getGroupPrivileges($id)
    {
        if (!$this->groupsFetched) {
            $this->fetchGroups();
        }

        if (isset($this->groups[$id])) {
            $group = $this->groups[$id];
            unset($group['id']);
            unset($group['name']);

            return $group;
        }

        return null;
    }

    public function getGroupsAmount()
    {
        return count($this->groups);
    }

    private function fetchGroups()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "groups`");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->groups[$row['id']] = $row;
        }

        $this->groupsFetched = true;
    }

    /**
     * Add JS script
     *
     * @param string $path
     */
    public function scriptAdd($path)
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
    public function styleAdd($path)
    {
        if (!in_array($path, $this->styles)) {
            $this->styles[] = $path;
        }
    }

    public function scriptsGet()
    {
        $output = [];
        foreach ($this->scripts as $script) {
            $output[] = "<script type=\"text/javascript\" src=\"{$script}\"></script>";
        }

        return implode("\n", $output);
    }

    public function stylesGet()
    {
        $output = [];
        foreach ($this->styles as $style) {
            $output[] = "<link href=\"{$style}\" rel=\"stylesheet\" />";
        }

        return implode("\n", $output);
    }

    public function getGoogleAnalytics()
    {
        return strlen($this->settings['google_analytics'])
            ? $this->template->render('google_analytics', ['settings' => $this->settings])
            : '';
    }
}
