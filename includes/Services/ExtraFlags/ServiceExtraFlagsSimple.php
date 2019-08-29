<?php
namespace App\Services\ExtraFlags;

use Admin\Table;
use App\CurrentPage;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceCreate;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Service;
use App\Settings;
use App\TranslationManager;
use App\Translator;

class ServiceExtraFlagsSimple extends Service implements
    IServiceAdminManage,
    IServiceCreate,
    IServiceAvailableOnServers,
    IServiceUserServiceAdminDisplay
{
    const MODULE_ID = "extra_flags";
    const USER_SERVICE_TABLE = "user_service_extra_flags";

    /** @var Translator */
    protected $lang;

    /** @var Translator */
    protected $langShop;

    /** @var Settings */
    protected $settings;

    public function __construct($service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->settings = $this->app->make(Settings::class);
    }

    public function service_admin_extra_fields_get()
    {
        // WEB
        if ($this->show_on_web()) {
            $web_sel_yes = "selected";
        } else {
            $web_sel_no = "selected";
        }

        // Nick, IP, SID
        $types = "";
        for ($i = 0, $option_id = 1; $i < 3; $option_id = 1 << ++$i) {
            $types .= create_dom_element("option", $this->get_type_name($option_id), [
                'value' => $option_id,
                'selected' =>
                    $this->service !== null && $this->service['types'] & $option_id
                        ? "selected"
                        : "",
            ]);
        }

        // Pobieramy flagi, jeżeli service nie jest puste
        // czyli kiedy edytujemy, a nie dodajemy usługę
        if ($this->service !== null) {
            $flags = $this->service['flags_hsafe'];
        }

        return $this->template->render(
            "services/extra_flags/extra_fields",
            compact('web_sel_no', 'web_sel_yes', 'types', 'flags') + [
                'moduleId' => $this->get_module_id(),
            ],
            true,
            false
        );
    }

    public function service_admin_manage_pre($data)
    {
        $warnings = [];

        // Web
        if (!in_array($data['web'], ["1", "0"])) {
            $warnings['web'][] = $this->lang->translate('only_yes_no');
        }

        // Flagi
        if (!strlen($data['flags'])) {
            $warnings['flags'][] = $this->lang->translate('field_no_empty');
        } else {
            if (strlen($data['flags']) > 25) {
                $warnings['flags'][] = $this->lang->translate('too_many_flags');
            } else {
                if (implode('', array_unique(str_split($data['flags']))) != $data['flags']) {
                    $warnings['flags'][] = $this->lang->translate('same_flags');
                }
            }
        }

        // Typy
        if (empty($data['type'])) {
            $warnings['type[]'][] = $this->lang->translate('no_type_chosen');
        }

        // Sprawdzamy, czy typy są prawidłowe
        foreach ($data['type'] as $type) {
            if (
                !(
                    $type &
                    (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID)
                )
            ) {
                $warnings['type[]'][] = $this->lang->translate('wrong_type_chosen');
                break;
            }
        }

        return $warnings;
    }

    public function service_admin_manage_post($data)
    {
        // Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
        $types = 0;
        foreach ($data['type'] as $type) {
            $types |= $type;
        }

        $extra_data = $this->service['data'];
        $extra_data['web'] = $data['web'];

        // Tworzymy plik z opisem usługi
        $file = $this->app->path(
            "themes/{$this->settings['theme']}/services/" .
                escape_filename($data['id']) .
                "_desc.html"
        );
        if (!file_exists($file)) {
            file_put_contents($file, "");

            // Dodajemy uprawnienia
            chmod($file, 0777);

            // Sprawdzamy czy uprawnienia się dodały
            if (substr(sprintf('%o', fileperms($file)), -4) != "0777") {
                json_output(
                    "not_created",
                    $this->lang->sprintf(
                        $this->lang->translate('wrong_service_description_file'),
                        $this->settings['theme']
                    ),
                    0
                );
            }
        }

        return [
            'query_set' => [
                [
                    'type' => '%d',
                    'column' => 'types',
                    'value' => $types,
                ],
                [
                    'type' => '%s',
                    'column' => 'flags',
                    'value' => $data['flags'],
                ],
                [
                    'type' => '%s',
                    'column' => 'data',
                    'value' => json_encode($extra_data),
                ],
            ],
        ];
    }

    // Zwraca nazwę typu
    protected function get_type_name($value)
    {
        if ($value == ExtraFlagType::TYPE_NICK) {
            return $this->lang->translate('nickpass');
        }

        if ($value == ExtraFlagType::TYPE_IP) {
            return $this->lang->translate('ippass');
        }

        if ($value == ExtraFlagType::TYPE_SID) {
            return $this->lang->translate('sid');
        }

        return "";
    }

    protected function get_type_name2($value)
    {
        if ($value == ExtraFlagType::TYPE_NICK) {
            return $this->lang->translate('nick');
        }

        if ($value == ExtraFlagType::TYPE_IP) {
            return $this->lang->translate('ip');
        }

        if ($value == ExtraFlagType::TYPE_SID) {
            return $this->lang->translate('sid');
        }

        return "";
    }

    // ----------------------------------------------------------------------------------
    // ### Wyświetlanie usług użytkowników w PA

    public function user_service_admin_display_title_get()
    {
        return $this->lang->translate('extra_flags');
    }

    public function user_service_admin_display_get($get, $post)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        $pageNumber = $currentPage->getPageNumber();

        $wrapper = new Table\Wrapper();
        $wrapper->setSearch();

        $table = new Table\Structure();

        $cell = new Table\Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Table\Cell($this->lang->translate('user')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('server')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('service')));
        $table->addHeadCell(
            new Table\Cell(
                "{$this->lang->translate('nick')}/{$this->lang->translate(
                    'ip'
                )}/{$this->lang->translate('sid')}"
            )
        );
        $table->addHeadCell(new Table\Cell($this->lang->translate('expires')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($get['search'])) {
            searchWhere(
                ["us.id", "us.uid", "u.username", "srv.name", "s.name", "usef.auth_data"],
                $get['search'],
                $where
            );
        }
        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS us.id AS `id`, us.uid AS `uid`, u.username AS `username`, " .
                "srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
                "usef.type AS `type`, usef.auth_data AS `auth_data`, us.expire AS `expire` " .
                "FROM `" .
                TABLE_PREFIX .
                "user_service` AS us " .
                "INNER JOIN `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` AS usef ON usef.us_id = us.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "services` AS s ON s.id = usef.service " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "servers` AS srv ON srv.id = usef.server " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT " .
                get_row_limit($pageNumber)
        );

        $table->setDbRowsAmount($this->db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new Table\BodyRow();

            $body_row->setDbId($row['id']);
            $body_row->addCell(
                new Table\Cell(
                    $row['uid']
                        ? $row['username'] . " ({$row['uid']})"
                        : $this->lang->translate('none')
                )
            );
            $body_row->addCell(new Table\Cell($row['server']));
            $body_row->addCell(new Table\Cell($row['service']));
            $body_row->addCell(new Table\Cell($row['auth_data']));
            $body_row->addCell(
                new Table\Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $body_row->setButtonDelete();
                $body_row->setButtonEdit();
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }

    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param \App\Models\Purchase $purchase_data
     *
     * @return integer        value returned by function add_bought_service_info
     */
    public function purchase($purchase_data)
    {
        // TODO: Implement purchase() method.
    }

    /**
     * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
     * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
     *
     * @param \App\Models\Purchase $purchase_data
     *
     * @return array
     *  status => string id wiadomości,
     *  text => string treść wiadomości
     *  positive => bool czy udało się przeprowadzić zakup czy nie
     *  [data => array('warnings' => array())]
     *  [purchase_data => Entity_Purchase dane zakupu]
     */
    public function purchase_data_validate($purchase_data)
    {
        // TODO: Implement purchase_data_validate() method.
    }
}
