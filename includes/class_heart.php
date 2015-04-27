<?php

class Heart
{

    private $servers = array();
    private $servers_fetched;

    private $services = array();
    private $services_fetched;

    private $tariffs = array();
    private $tariffs_fetched;

    private $page = array();
    public $page_title;

    private $services_classes;
    private $payment_api_classes;

    private $users;

    private $groups;
    private $groups_fetched;

    function __construct()
    {
        $this->servers_fetched = false;
        $this->services_fetched = false;
        $this->tariffs_fetched = false;
        $this->groups_fetched = false;
        $this->services_classes = array();
        $this->payment_api_classes = array();
        $this->users = array();
        $this->groups = array();
    }

    //
    // Klasy usług
    //

    public function register_service_module($id, $name, $class, $classsimple)
    {
        if (isset($this->services_classes[$id]))
            throw new Exception('There is already a service with such an id.');

        $this->services_classes[$id] = array(
            'name' => $name,
            'class' => $class,
            'classsimple' => $classsimple
        );
    }

    /**
     * Zwraca obiekt modułu usługi
     * Moduł jest wypełniony, są w nim wszystkie dane
     *
     * @param $service_id
     * @return null|Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther
     */
    public function get_service_module($service_id)
    {
        // Brak usługi o takim ID
        if (is_null($service = $this->get_service($service_id)))
            return NULL;

        // Brak takiego modułu
        if (!isset($this->services_classes[$service['module']]))
            return NULL;

        $className = $this->services_classes[$service['module']]['class'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return $className ? new $className($service) : NULL;
    }

    // Funkcja zwraca klasę modułu przez jego id
    // Moduł jest pusty, nie ma danych o usłudze
    // s - simple
    public function get_service_module_s($module_id)
    {
        // Brak takiego modułu
        if (!isset($this->services_classes[$module_id]))
            return NULL;

        $className = $this->services_classes[$module_id]['classsimple'];

        // Jeszcze sprawdzamy, czy moduł został prawidłowo stworzony
        return $className ? new $className() : NULL;
    }

    public function get_service_module_name($module_id)
    {
        // Brak takiego modułu
        if (!isset($this->services_classes[$module_id]))
            return NULL;

        return $this->services_classes[$module_id]['name'];
    }

    /**
     * Zwraca wszystkie zarejestrowane moduły usług
     *
     * @return array
     */
    public function get_services_modules()
    {
        $modules = array();
        foreach ($this->services_classes as $id => $data) {
            $modules[] = array(
                'id' => $id,
                'name' => $data['name']
            );
        }

        return $modules;
    }

    //
    // Klasy API płatności
    //

    public function register_payment_api($id, $class)
    {
        $this->payment_api_classes[$id] = $class;
    }

    public function get_payment_api($id)
    {
        return isset($this->payment_api_classes[$id]) ? $this->payment_api_classes[$id] : NULL;
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
     * @return mixed
     */
    public function get_service($service_id)
    {
        if (!$this->services_fetched) {
            $this->fetch_services();
        }

        return if_isset($this->services[$service_id], NULL);
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
        global $db;

        $result = $db->query(
            "SELECT * FROM `" . TABLE_PREFIX . "services` " .
            "ORDER BY `order` ASC"
        );
        while ($row = $db->fetch_array_assoc($result)) {
            $row['id_hsafe'] = htmlspecialchars($row['id']);
            $row['name'] = htmlspecialchars($row['name']);
            $row['groups'] = $row['groups'] ? explode(";", $row['groups']) : array();
            $row['data'] = json_decode($row['data'], true);
            $this->services[$row['id']] = $row;
        }
        $this->services_fetched = true;
    }

    public function user_can_use_service($uid, $service)
    {
        $user = $this->get_user($uid);
        $combined = array_intersect($service['groups'], $user['groups']);
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

        return if_isset($this->servers[$id], NULL);
    }

    public function get_servers_amount()
    {
        return count($this->servers);
    }

    private function fetch_servers()
    {
        global $db;

        $result = $db->query("SELECT * FROM `" . TABLE_PREFIX . "servers`");
        while ($row = $db->fetch_array_assoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->servers[$row['id']] = $row;
        }
        $this->servers_fetched = true;
    }

    //
    // TARYFY
    //

    public function get_tariffs()
    {
        if (!$this->tariffs_fetched) {
            $this->fetch_tariffs();
        }

        return $this->tariffs;
    }

    public function get_tariff($id)
    {
        if (!$this->tariffs_fetched) {
            $this->fetch_tariffs();
        }

        return if_isset($this->tariffs[$id], NULL);
    }

    public function get_tariff_provision($id)
    {
        if (!$this->tariffs_fetched) {
            $this->fetch_tariffs();
        }

        return if_isset($this->tariffs[$id]['provision'], NULL);
    }

    public function get_tariffs_amount()
    {
        return count($this->tariffs);
    }

    private function fetch_tariffs()
    {
        global $db;

        $result = $db->query("SELECT * FROM `" . TABLE_PREFIX . "tariffs`");
        while ($row = $db->fetch_array_assoc($result)) {
            $this->tariffs[$row['tariff']] = $row;
        }

        $this->tariffs_fetched = true;
    }

    //
    // Użytkownicy
    //

    public function get_user($uid, $login = "", $password = "")
    {
        global $db;

        // Wcześniej już pobraliśmy takiego użytkownika
        if ($uid != "" && isset($this->users[$uid])) {
            return $this->users[$uid];
        }

        if ($uid != "" || ($login != "" && $password != "")) {
            $result = $db->query($db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "users` " .
                "WHERE `uid` = '%d' OR ((username = '%s' OR email = '%s') AND PASSWORD = md5( CONCAT( md5('%s'), md5(salt) ) ))",
                array($uid, $login, $login, $password)
            ));

            if ($db->num_rows($result)) {
                $user = $db->fetch_array_assoc($result);
                $user['wallet'] = number_format($user['wallet'], 2);
                $user['forename'] = htmlspecialchars($user['forename']);
                $user['surname'] = htmlspecialchars($user['surname']);
                $user['email'] = htmlspecialchars($user['email']);
                $user['username'] = htmlspecialchars($user['username']);
                $user['groups'] = explode(';', $user['groups']);
            }
        }

        // Pobieramy uprawnienia gracza w jedno miejsce
        $user['privilages'] = array();
        foreach ($user['groups'] as $gid) {
            $group = $this->get_group_privilages($gid);
            foreach ($group as $priv => $value)
                if ($value) $user['privilages'][$priv] = true;
        }

        $user['platform'] = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
        $user['ip'] = get_ip();

        $this->users[$user['uid']] = $user;

        return $user;
    }

    public function has_user_group($uid, $gid)
    {
        $user = $this->get_user($uid);
        return in_array($gid, $user['groups']);
    }

    //
    // Grupy
    //

    public function get_groups()
    {
        if (!$this->groups_fetched)
            $this->fetch_groups();

        return $this->groups;
    }

    public function get_group($id)
    {
        if (!$this->groups_fetched)
            $this->fetch_groups();

        return if_isset($this->groups[$id], NULL);
    }

    public function get_group_privilages($id)
    {
        if (!$this->groups_fetched)
            $this->fetch_groups();

        if (isset($this->groups[$id])) {
            $group = $this->groups[$id];
            unset($group['id']);
            unset($group['name']);

            return $group;
        }

        return NULL;
    }

    public function get_groups_amount()
    {
        return count($this->groups);
    }

    private function fetch_groups()
    {
        global $db;

        $result = $db->query("SELECT * FROM `" . TABLE_PREFIX . "groups`");
        while ($row = $db->fetch_array_assoc($result)) {
            $row['name'] = htmlspecialchars($row['name']);
            $this->groups[$row['id']] = $row;
        }

        $this->groups_fetched = true;
    }

    //
    // Informacje o stronie
    //

    public function get_page($id)
    {
        if (!isset($this->page[$id])) {
            $this->fetch_page($id);
        }

        $this->page_title = isset($this->page[$id]) ? $this->page[$id]['title'] : $this->page['main_content']['title'];
        return isset($this->page[$id]) ? $this->page[$id] : $this->page['main_content'];
    }

    private function fetch_page($id)
    {
        global $db;

        // Pobieranie info o danej podstronie
        $result = $db->query($db->prepare(
            "SELECT * FROM `" . TABLE_PREFIX . "pages` " .
            "WHERE `id` = '%s' OR `id` = 'main_content'",
            array($id)
        ));

        // Pobieramy strone
        $row = $db->fetch_array_assoc($result);
        // Jeżeli sa dwie strony, to sprawdzamy, czy nie pobralismy przypadkiem main_content
        if ($db->num_rows($result) == 2) {
            // Jesteśmy przy main_content, więc pobieramy jeszcze raz
            if ($row['id'] != $id)
                $row = $db->fetch_array_assoc($result);
        }

        // Przypisujemy pobraną stronę
        $this->page[$row['id']] = $row;
        $this->page['id_safe'] = htmlspecialchars($row['id']);
    }

}