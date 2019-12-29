<?php
namespace App\Services\ExtraFlags;

use App\Exceptions\InvalidConfigException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceCreate;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Service;
use App\System\CurrentPage;
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class ServiceExtraFlagsSimple extends Service implements
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

    /** @var Path */
    protected $path;

    public function __construct(\App\Models\Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->settings = $this->app->make(Settings::class);
        $this->path = $this->app->make(Path::class);
    }

    public function serviceAdminExtraFieldsGet()
    {
        // WEB
        $webSelYes = $this->showOnWeb() ? "selected" : "";
        $webSelNo = $this->showOnWeb() ? "" : "selected";

        // Nick, IP, SID
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            $types .= create_dom_element("option", $this->getTypeName($optionId), [
                'value' => $optionId,
                'selected' =>
                    $this->service !== null && $this->service->getTypes() & $optionId
                        ? "selected"
                        : "",
            ]);
        }

        // Pobieramy flagi, jeżeli service nie jest puste
        // czyli kiedy edytujemy, a nie dodajemy usługę
        $flags = $this->service ? $this->service->getFlags() : "";

        return $this->template->render(
            "services/extra_flags/extra_fields",
            compact('webSelNo', 'webSelYes', 'types', 'flags') + [
                'moduleId' => $this->getModuleId(),
            ],
            true,
            false
        );
    }

    public function serviceAdminManagePre(array $data)
    {
        $warnings = [];

        $web = array_get($data, 'web');
        $flags = array_get($data, 'flags');
        $types = array_get($data, 'type', []);

        // Web
        if (!in_array($web, ["1", "0"])) {
            $warnings['web'][] = $this->lang->translate('only_yes_no');
        }

        // Flagi
        if (!strlen($flags)) {
            $warnings['flags'][] = $this->lang->translate('field_no_empty');
        } elseif (strlen($flags) > 25) {
            $warnings['flags'][] = $this->lang->translate('too_many_flags');
        } elseif (implode('', array_unique(str_split($flags))) != $flags) {
            $warnings['flags'][] = $this->lang->translate('same_flags');
        }

        // Typy
        if (empty($types)) {
            $warnings['type[]'][] = $this->lang->translate('no_type_chosen');
        }

        // Sprawdzamy, czy typy są prawidłowe
        foreach ($types as $type) {
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

    public function serviceAdminManagePost(array $data)
    {
        // Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
        $types = 0;
        foreach ($data['type'] as $type) {
            $types |= $type;
        }

        $extraData = $this->service ? $this->service->getData() : [];
        $extraData['web'] = $data['web'];

        // Tworzymy plik z opisem usługi
        $file = $this->path->to(
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
                throw new InvalidConfigException(
                    $this->lang->sprintf(
                        $this->lang->translate('wrong_service_description_file'),
                        $this->settings['theme']
                    )
                );
            }
        }

        return [
            'types' => $types,
            'flags' => $data['flags'],
            'data' => $extraData,
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

        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('server')));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(
            new HeadCell(
                "{$this->lang->translate('nick')}/{$this->lang->translate(
                    'ip'
                )}/{$this->lang->translate('sid')}"
            )
        );
        $table->addHeadCell(new HeadCell($this->lang->translate('expires')));

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
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid']
                        ? "{$row['username']} ({$row['uid']})"
                        : $this->lang->translate('none')
                )
            );
            $bodyRow->addCell(new Cell($row['server']));
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['auth_data']));
            $bodyRow->addCell(
                new Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $bodyRow->setDeleteAction();
                $bodyRow->setEditAction();
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }
}
