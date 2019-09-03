<?php
namespace App\Services\ExtraFlags;

use Admin\Table;
use App\CurrentPage;
use App\Models\Purchase;
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

    public function serviceAdminExtraFieldsGet()
    {
        // WEB
        if ($this->showOnWeb()) {
            $webSelYes = "selected";
        } else {
            $webSelNo = "selected";
        }

        // Nick, IP, SID
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            $types .= create_dom_element("option", $this->getTypeName($optionId), [
                'value' => $optionId,
                'selected' =>
                    $this->service !== null && $this->service['types'] & $optionId
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
            compact('webSelNo', 'webSelYes', 'types', 'flags') + [
                'moduleId' => $this->getModuleId(),
            ],
            true,
            false
        );
    }

    public function serviceAdminManagePre($data)
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

    public function serviceAdminManagePost($data)
    {
        // Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
        $types = 0;
        foreach ($data['type'] as $type) {
            $types |= $type;
        }

        $extraData = $this->service['data'];
        $extraData['web'] = $data['web'];

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
                    'value' => json_encode($extraData),
                ],
            ],
        ];
    }

    protected function getTypeName($value)
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

    protected function getTypeName2($value)
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

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->translate('extra_flags');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
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
        if (isset($query['search'])) {
            searchWhere(
                ["us.id", "us.uid", "u.username", "srv.name", "s.name", "usef.auth_data"],
                $query['search'],
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

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new Table\BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Table\Cell(
                    $row['uid']
                        ? $row['username'] . " ({$row['uid']})"
                        : $this->lang->translate('none')
                )
            );
            $bodyRow->addCell(new Table\Cell($row['server']));
            $bodyRow->addCell(new Table\Cell($row['service']));
            $bodyRow->addCell(new Table\Cell($row['auth_data']));
            $bodyRow->addCell(
                new Table\Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $bodyRow->setButtonDelete();
                $bodyRow->setButtonEdit();
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }

    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param \App\Models\Purchase $purchaseData
     *
     * @return integer        value returned by function add_bought_service_info
     */
    public function purchase(Purchase $purchaseData)
    {
        //
    }

    /**
     * Metoda która sprawdza poprawność wprowadzonych danych zakupu,
     * wywoływana gdy zakup został przeprowadzony z zewnątrz, nie przez formularz na stronie WWW.
     *
     * @param \App\Models\Purchase $purchaseData
     *
     * @return array
     *  status => string id wiadomości,
     *  text => string treść wiadomości
     *  positive => bool czy udało się przeprowadzić zakup czy nie
     *  [data => array('warnings' => array())]
     *  [purchase_data => Entity_Purchase dane zakupu]
     */
    public function purchaseDataValidate(Purchase $purchaseData)
    {
        //
    }
}
