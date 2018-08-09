<?php

use App\Application;
use App\Database;
use App\Template;

abstract class Service
{
    const MODULE_ID = '';
    const USER_SERVICE_TABLE = '';
    public $service = [];

    /** @var Application */
    protected $app;

    /** @var Template */
    protected $template;

    /** @var Database */
    protected $db;

    public function __construct($service = null)
    {
        $this->app = app();
        $this->template = $this->app->make(Template::class);
        $this->db = $this->app->make(Database::class);

        if (!is_array($service)) { // Podano błędne dane usługi
            $this->service = null;
            return;
        }

        $this->service = $service;
    }

    /**
     * Metoda wywoływana, gdy usługa jest usuwana.
     *
     * @param integer $service_id ID usługi
     */
    public function service_delete($service_id)
    {
    }

    /**
     * Metoda wywoływana przy usuwaniu usługi użytkownika.
     *
     * @param array $user_service Dane o usłudze z bazy danych
     * @param string $who Kto wywołał akcję ( admin, task )
     *
     * @return bool
     */
    public function user_service_delete($user_service, $who)
    {
        return true;
    }

    /**
     * Metoda wywoływana po usunięciu usługi użytkownika.
     *
     * @param array $user_service Dane o usłudze z bazy danych
     */
    public function user_service_delete_post($user_service)
    {
    }

    /**
     * Metoda powinna zwrócić, czy usługa ma być wyświetlana na stronie WWW.
     */
    public function show_on_web()
    {
        if ($this->service !== null) {
            return $this->service['data']['web'];
        }

        return false;
    }

    /**
     * Super krotki opis to 28 znakow, przeznaczony jest tylko na serwery
     * Krotki opis, to 'description', krótki na strone WEB
     * Pełny opis, to plik z opisem całej usługi
     *
     * @return string    Description
     */
    public function description_full_get()
    {
        $file = "services/" . escape_filename($this->service['id']) . "_desc";
        return eval($this->template->render($file, true, false));
    }

    public function description_short_get()
    {
        return $this->service['description'];
    }

    public function get_module_id()
    {
        return $this::MODULE_ID;
    }

    /**
     * Aktualizuje usługę gracza
     *
     * @param array $set (column, value, data)
     * @param string $where1 Where dla update na tabeli user_service
     * @param string $where2 Where dla update na tabeli modułu
     *
     * @return int Ilosc wierszy które zostały zaktualizowane
     */
    protected function update_user_service($set, $where1, $where2)
    {
        $set_data1 = $set_data2 = $where_data = $where_data2 = [];

        foreach ($set as $data) {
            $set_data = $this->db->prepare(
                "`{$data['column']}` = {$data['value']}",
                if_isset($data['data'], [])
            );

            if (in_array($data['column'], ['uid', 'service', 'expire'])) {
                $set_data1[] = $set_data;
            } else {
                $set_data2[] = $set_data;
            }

            // Service jest w obu tabelach
            if ($data['column'] == 'service') {
                $set_data2[] = $set_data;
            }
        }

        if (my_is_integer($where1)) {
            $where1 = "WHERE `id` = {$where1}";
        } else {
            if (strlen($where1)) {
                $where1 = "WHERE {$where1}";
            }
        }

        if (my_is_integer($where2)) {
            $where2 = "WHERE `us_id` = {$where2}";
        } else {
            if (strlen($where2)) {
                $where2 = "WHERE {$where2}";
            }
        }

        $affected = 0;
        if (!empty($set_data1)) {
            $this->db->query(
                "UPDATE `" . TABLE_PREFIX . "user_service` " .
                "SET " . implode(', ', $set_data1) . " " .
                $where1
            );
            $affected = max($affected, $this->db->affected_rows());
        }

        if (!empty($set_data2)) {
            $this->db->query(
                "UPDATE `" . TABLE_PREFIX . $this::USER_SERVICE_TABLE . "` " .
                "SET " . implode(', ', $set_data2) . " " .
                $where2
            );
            $affected = max($affected, $this->db->affected_rows());
        }

        return $affected;
    }
}
