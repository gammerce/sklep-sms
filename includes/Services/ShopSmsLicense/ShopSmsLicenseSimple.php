<?php
namespace App\Services\ShopSmsLicense;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;
use App\LicenseServerService;
use App\Payment\BoughtServiceService;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Service;
use App\System\Auth;
use App\System\CurrentPage;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ShopSmsLicenseSimple extends Service implements IServiceUserServiceAdminDisplay
{
    const MODULE_ID = "shopsms_license";
    const USER_SERVICE_TABLE = "user_service_shopsms_license";
    // Kwoty za dzień w groszach
    const COST_SHOP_PER_DAY = 40;
    const COST_ENGINE_PER_DAY = 20;

    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    /** @var Auth */
    protected $auth;

    /** @var BoughtServiceService */
    protected $boughtServiceService;

    /** @var CurrentPage */
    protected $currentPage;

    /** @var LicenseServerService */
    protected $licenseServerService;

    public function __construct($service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->auth = $this->app->make(Auth::class);
        $this->auth = $this->app->make(Auth::class);
        $this->currentPage = $this->app->make(CurrentPage::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
    }

    /**
     * Zwraca tytuł strony, gdy włączona jest lista usług użytkowników
     *
     * @return string
     */
    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->translate('licenses');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('identifier')));
        $table->addHeadCell(new HeadCell($this->lang->translate('external_license_id')));
        $table->addHeadCell(new HeadCell($this->lang->translate('cost_daily')));
        $table->addHeadCell(new HeadCell($this->lang->translate('expires')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($query['search'])) {
            searchWhere(
                [
                    "us.id",
                    "us.uid",
                    "u.username",
                    "s.name",
                    "m.external_license_id",
                    "m.identifier",
                    'm.cost_daily',
                ],
                urldecode($query['search']),
                $where
            );
        }
        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS us.id, us.uid, u.username, s.id AS `service_id`, " .
                "s.name AS `service`, us.expire, m.identifier, m.external_license_id, m.cost_daily " .
                "FROM `" .
                TABLE_PREFIX .
                "user_service` AS us " .
                "INNER JOIN `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` AS m ON m.us_id = us.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "services` AS s ON s.id = m.service " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid']
                        ? $row['username'] . " ({$row['uid']})"
                        : $this->lang->translate('none')
                )
            );
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['identifier']));
            $bodyRow->addCell(new Cell($row['external_license_id']));
            $bodyRow->addCell(
                new Cell(
                    number_format($row['cost_daily'] / 100, 2) . ' ' . $this->settings['currency']
                )
            );
            $bodyRow->addCell(
                new Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(false);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }
}
