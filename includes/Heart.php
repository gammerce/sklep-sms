<?php
namespace App;

use Block;
use BlockSimple;
use Entity_Tariff;
use Entity_User;
use Exception;
use IPageAdmin_ActionBox;
use Page;
use PageSimple;
use Service;
use ServiceChargeWallet;
use ServiceExtraFlags;
use ServiceOther;

class Heart
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var Template */
    private $template;

    private $servers = [];

    private $servers_fetched = false;
    private $services = [];

    private $services_fetched = false;
    private $servers_services = [];

    private $servers_services_fetched = false;

    /** @var Entity_Tariff[] */
    private $tariffs = [];
    private $tariffs_fetched = false;
    public $page_title;
    private $services_classes = [];

    private $payment_module_classes = [];

    private $pages_classes = [];
    private $blocks_classes = [];

    /** @var array Entity_User[] */
    private $users = [];
    private $groups = [];
    private $groups_fetched = false;
    private $scripts = [];
    private $styles = [];

    public function __construct(Database $db, Settings $settings, Template $template)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->template = $template;
    }

    /**
     * Rejestruje moduł usługi
     *
     * @param string $id identyfikator modułu
     * @param string $name nazwa modułu
     * @param string $class klasa modułu
     * @param string $classsimple klasa simple modułu
     *
     * @throws Exception
     */
    public function register_service_module($id, $name, $class, $classsimple)
    {
        if (isset($this->services_classes[$id])) {
            throw new Exception("There is a service with such an id: " . htmlspecialchars($id) . " already.");
        }

        $this->services_classes[$id] = [
            'name'        => $name,
            'class'       => $class,
            'classsimple' => $classsimple,
        ];
    }

    /**
     * Zwraca obiekt modułu usługi
     * Moduł jest wypełniony, są w nim wszystkie dane
     *
     * @param $service_id
     *
     * @return null|Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther
     */
    public function get_service_module($service_id)
    {
        // Brak usługi o takim ID
        if (($service = $this->get_service($service_id)) === null) {
            return null;
        }

        // Brak takiego modułu
        if (!isset($this->services_classes[$service['module']])) {
            return null;
        }

        $className = $this->services_classes[$service['module']]['class'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return strlen($className) ? new $className($service) : null;
    }

    // Funkcja zwraca klasę modułu przez jego id
    // Moduł jest pusty, nie ma danych o usłudze
    // s - simple
    public function get_service_module_s($module_id)
    {
        // Brak takiego modułu
        if (!isset($this->services_classes[$module_id])) {
            return null;
        }

        if (!isset($this->services_classes[$module_id]['classsimple'])) {
            return null;
        }

        $classname = $this->services_classes[$module_id]['classsimple'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return app()->make($classname);
    }

    public function get_service_module_name($module_id)
    {
        // Brak takiego modułu
        if (!isset($this->services_classes[$module_id])) {
            return null;
        }

        return $this->services_classes[$module_id]['name'];
    }

    /**
     * Zwraca wszystkie zarejestrowane moduły usług
     *
     * @return array
     */
    public function get_services_modules()
    {
        $modules = [];
        foreach ($this->services_classes as $id => $data) {
            $modules[] = [
                'id'          => $id,
                'name'        => $data['name'],
                'class'       => $data['class'],
                'classsimple' => $data['classsimple'],
            ];
        }

        return $modules;
    }

    //
    // Klasy API płatności
    //

    public function register_payment_module($id, $class)
    {
        if (isset($this->payment_module_classes[$id])) {
            throw new Exception("There is a payment api with id: " . htmlspecialchars($id) . " already.");
        }

        $this->payment_module_classes[$id] = $class;
    }

    public function get_payment_module($id)
    {
        return isset($this->payment_module_classes[$id]) ? $this->payment_module_classes[$id] : null;
    }

    //
    // Obsługa bloków
    //

    /**
     * Rejestruje blok
     *
     * @param string $block_id
     * @param string $class
     *
     * @throws Exception
     */
    public function register_block($block_id, $class)
    {
        if ($this->block_exists($block_id)) {
            throw new Exception("There is a block with such an id: " . htmlspecialchars($block_id) . " already.");
        }

        $this->blocks_classes[$block_id] = $class;
    }

    /**
     * Sprawdza czy dany blok istnieje
     *
     * @param string $block_id
     *
     * @return bool
     */
    public function block_exists($block_id)
    {
        return isset($this->blocks_classes[$block_id]);
    }

    /**
     * Zwraca obiekt bloku
     *
     * @param string $block_id
     *
     * @return null|Block|BlockSimple
     */
    public function get_block($block_id)
    {
        return $this->block_exists($block_id) ? app()->make($this->blocks_classes[$block_id]) : null;
    }

    //
    // Obsługa stron
    //

    /**
     * Rejestruje strone
     *
     * @param string $page_id
     * @param string $class
     * @param string $type
     *
     * @throws Exception
     */
    public function register_page($page_id, $class, $type = "user")
    {
        if ($this->page_exists($page_id, $type)) {
            throw new Exception("There is a page with such an id: " . htmlspecialchars($page_id) . " already.");
        }

        $this->pages_classes[$type][$page_id] = $class;
    }

    /**
     * Sprawdza czy dana strona istnieje
     *
     * @param string $page_id
     * @param string $type
     *
     * @return bool
     */
    public function page_exists($page_id, $type = "user")
    {
        return isset($this->pages_classes[$type][$page_id]);
    }

    /**
     * Zwraca obiekt strony
     *
     * @param string $page_id
     * @param string $type
     *
     * @return null|Page|PageSimple|IPageAdmin_ActionBox
     */
    public function get_page($page_id, $type = "user")
    {
        if (!$this->page_exists($page_id, $type)) {
            return null;
        }

        $classname = $this->pages_classes[$type][$page_id];

        return app()->make($classname);
    }

    //
    // USŁUGI
    //

    /**
     * Zwraca wszystkie stworzone usługi do zakupienia
     *
     * @return array
     */
    public function get_services()
    {
        if (!$this->services_fetched) {
            $this->fetch_services();
        }

        return $this->services;
    }

    /**
     * Zwraca usługę do zakupienia
     *
     * @param $service_id
     *
     * @return mixed
     */
    public function get_service($service_id)
    {
        if (!$this->services_fetched) {
            $this->fetch_services();
        }

        return if_isset($this->services[$service_id], null);
    }

    /**
     * Zwraca ilość stworzonych usług do zakupienia
     *
     * @return int
     */
    public function get_services_amount()
    {
        return count($this->services);
    }

    private function fetch_services()
    {
        $result = $this->db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "services` " .
            "ORDER BY `order` ASC"
        );
        while ($row = $this->db->fetch_array_assoc($result)) {
            $row['id_hsafe'] = htmlspecialchars($row['id']);
            $row['name'] = htmlspecialchars($row['name']);
            $row['groups'] = $row['groups'] ? explode(";", $row['groups']) : [];
            $row['data'] = json_decode($row['data'], true);
            $this->services[$row['id']] = $row;
        }
        $this->services_fetched = true;
    }

    public function user_can_use_service($uid, $service)
    {
        $user = $this->get_user($uid);
        $combined = array_intersect($service['groups'], $user->getGroups());

        return empty($service['groups']) || !empty($combined);
    }

    //
    // SERWERY
    //

    public function get_servers()
    {
        if (!$this->servers_fetched) {
            $this->fetch_servers();
        }

        return $this->servers;
    }

    public function get_server($id)
    {
        if (!$this->servers_fetched) {
            $this->fetch_servers();
        }

        return if_isset($this->servers[$id], null);
    }

    public function get_servers_amount()
    {
        return count($this->servers);
    }

    private function fetch_servers()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers`");
        while ($row = $this->db->fetch_array_assoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->servers[$row['id']] = $row;
        }
        $this->servers_fetched = true;
    }

    //
    // Serwery - Usługi
    //

    /**
     * Sprawdza czy dana usluge mozne kupic na danym serwerze
     *
     * @param integer $server_id
     * @param string  $service_id
     *
     * @return boolean
     */
    public function server_service_linked($server_id, $service_id)
    {
        if (!$this->servers_services_fetched) {
            $this->fetch_servers_services();
        }

        return isset($this->servers_services[$server_id][$service_id]);
    }

    private function fetch_servers_services()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers_services`");
        while ($row = $this->db->fetch_array_assoc($result)) {
            $this->servers_services[$row['server_id']][$row['service_id']] = true;
        }
        $this->servers_services_fetched = true;
    }

    //
    // TARYFY
    //

    /**
     * @return Entity_Tariff[]
     */
    public function getTariffs()
    {
        if (!$this->tariffs_fetched) {
            $this->fetch_tariffs();
        }

        return $this->tariffs;
    }

    /**
     * @param int $id
     *
     * @return Entity_Tariff | null
     */
    public function getTariff($id)
    {
        if (!$this->tariffs_fetched) {
            $this->fetch_tariffs();
        }

        return if_isset($this->tariffs[$id], null);
    }

    public function getTariffsAmount()
    {
        return count($this->tariffs);
    }

    private function fetch_tariffs()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "tariffs`");
        while ($row = $this->db->fetch_array_assoc($result)) {
            $this->tariffs[$row['id']] = new Entity_Tariff($row['id'], $row['provision'], $row['predefined']);
        }

        $this->tariffs_fetched = true;
    }

    //
    // Użytkownicy
    //

    /**
     * @param int    $uid
     * @param string $login
     * @param string $password
     *
     * @return Entity_User
     */
    public function get_user($uid = 0, $login = "", $password = "")
    {
        // Wcześniej już pobraliśmy takiego użytkownika
        if ($uid && isset($this->users[$uid])) {
            return $this->users[$uid];
        }

        if ($uid || (strlen($login) && strlen($password))) {
            $user = new Entity_User($uid, $login, $password);
            $this->users[$user->getUid()] = $user;

            return $user;
        }

        return new Entity_User();
    }

    public function has_user_group($uid, $gid)
    {
        $user = $this->get_user($uid);

        return in_array($gid, $user->getGroups());
    }

    //
    // Grupy
    //

    public function get_groups()
    {
        if (!$this->groups_fetched) {
            $this->fetch_groups();
        }

        return $this->groups;
    }

    public function get_group($id)
    {
        if (!$this->groups_fetched) {
            $this->fetch_groups();
        }

        return if_isset($this->groups[$id], null);
    }

    public function get_group_privilages($id)
    {
        if (!$this->groups_fetched) {
            $this->fetch_groups();
        }

        if (isset($this->groups[$id])) {
            $group = $this->groups[$id];
            unset($group['id']);
            unset($group['name']);

            return $group;
        }

        return null;
    }

    public function get_groups_amount()
    {
        return count($this->groups);
    }

    private function fetch_groups()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "groups`");
        while ($row = $this->db->fetch_array_assoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->groups[$row['id']] = $row;
        }

        $this->groups_fetched = true;
    }

    /**
     * Dodaje skrypt js
     *
     * @param string $path
     */
    public function script_add($path)
    {
        if (!in_array($path, $this->scripts)) {
            $this->scripts[] = $path;
        }
    }

    /**
     * Dodaje szablon css
     *
     * @param string $path
     */
    public function style_add($path)
    {
        if (!in_array($path, $this->styles)) {
            $this->styles[] = $path;
        }
    }

    public function scripts_get()
    {
        $output = [];
        foreach ($this->scripts as $script) {
            $output[] = "<script type=\"text/javascript\" src=\"{$script}\"></script>";
        }

        return implode("\n", $output);
    }

    public function styles_get()
    {
        $output = [];
        foreach ($this->styles as $style) {
            $output[] = "<link href=\"{$style}\" rel=\"stylesheet\" />";
        }

        return implode("\n", $output);
    }

    public function getGoogleAnalytics()
    {
        return strlen($this->settings['google_analytics']) ? eval($this->template->render('google_analytics')) : '';
    }
}
