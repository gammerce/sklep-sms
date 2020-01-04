<?php
namespace App\Services;

use App\System\Application;
use App\System\Database;
use App\System\Template;

abstract class Service
{
    /**
     * Module identifier defined by inheriting class
     */
    const MODULE_ID = '';

    /**
     * Database table where user services are stored
     */
    const USER_SERVICE_TABLE = '';

    /** @var \App\Models\Service|null */
    public $service;

    /** @var Application */
    protected $app;

    /** @var Template */
    protected $template;

    /** @var Database */
    protected $db;

    public function __construct(\App\Models\Service $service = null)
    {
        $this->app = app();
        $this->template = $this->app->make(Template::class);
        $this->db = $this->app->make(Database::class);
        $this->service = $service;
    }

    /**
     * Metoda wywoływana, gdy usługa jest usuwana.
     *
     * @param integer $serviceId ID usługi
     */
    public function serviceDelete($serviceId)
    {
        //
    }

    /**
     * Metoda wywoływana przy usuwaniu usługi użytkownika.
     *
     * @param array  $userService Dane o usłudze z bazy danych
     * @param string $who Kto wywołał akcję ( admin, task )
     *
     * @return bool
     */
    public function userServiceDelete($userService, $who)
    {
        return true;
    }

    /**
     * Metoda wywoływana po usunięciu usługi użytkownika.
     *
     * @param array $userService Dane o usłudze z bazy danych
     */
    public function userServiceDeletePost($userService)
    {
    }

    /**
     * Metoda powinna zwrócić, czy usługa ma być wyświetlana na stronie WWW.
     */
    public function showOnWeb()
    {
        if ($this->service !== null) {
            return array_get($this->service->getData(), 'web', false);
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
    public function descriptionLongGet()
    {
        $file = "services/" . escape_filename($this->service->getId()) . "_desc";
        return $this->template->render($file, [], true, false);
    }

    public function descriptionShortGet()
    {
        return $this->service->getDescription();
    }

    public function getModuleId()
    {
        return $this::MODULE_ID;
    }

    /**
     * Aktualizuje usługę gracza
     *
     * @param array  $set (column, value, data)
     * @param string $where1 Where dla update na tabeli user_service
     * @param string $where2 Where dla update na tabeli modułu
     *
     * @return int Ilosc wierszy które zostały zaktualizowane
     */
    protected function updateUserService($set, $where1, $where2)
    {
        $setData1 = $setData2 = $whereData = $whereData2 = [];

        foreach ($set as $data) {
            $setData = $this->db->prepare(
                "`{$data['column']}` = {$data['value']}",
                array_get($data, 'data', [])
            );

            if (in_array($data['column'], ['uid', 'service', 'expire'])) {
                $setData1[] = $setData;
            } else {
                $setData2[] = $setData;
            }

            // Service jest w obu tabelach
            if ($data['column'] == 'service') {
                $setData2[] = $setData;
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
        if (!empty($setData1)) {
            $statement = $this->db->query(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET " .
                    implode(', ', $setData1) .
                    " " .
                    $where1
            );
            $affected = max($affected, $statement->rowCount());
        }

        if (!empty($setData2)) {
            $statement = $this->db->query(
                "UPDATE `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "SET " .
                    implode(', ', $setData2) .
                    " " .
                    $where2
            );
            $affected = max($affected, $statement->rowCount());
        }

        return $affected;
    }
}
