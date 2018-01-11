<?php
namespace App\Services\ShopSmsLicense;

use Admin\Table;
use App\Auth;
use App\CurrentPage;
use App\LicenseServerService;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Service;
use App\Settings;
use App\TranslationManager;
use App\Translator;

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
        $this->currentPage = $this->app->make(CurrentPage::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
    }

    /**
     * Zwraca tytuł strony, gdy włączona jest lista usług użytkowników
     *
     * @return string
     */
    public function user_service_admin_display_title_get()
    {
        return $this->lang->translate('licenses');
    }

    public function user_service_admin_display_get($get, $post)
    {
        $wrapper = new Table\Wrapper();
        $wrapper->setSearch();

        $table = new Table\Structure();

        $cell = new Table\Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Table\Cell($this->lang->translate('user')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('service')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('identifier')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('external_license_id')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('cost_daily')));
        $table->addHeadCell(new Table\Cell($this->lang->translate('expires')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($get['search'])) {
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
                urldecode($get['search']),
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
            $body_row->addCell(new Table\Cell($row['service']));
            $body_row->addCell(new Table\Cell($row['identifier']));
            $body_row->addCell(new Table\Cell($row['external_license_id']));
            $body_row->addCell(
                new Table\Cell(
                    number_format($row['cost_daily'] / 100, 2) . ' ' . $this->settings['currency']
                )
            );
            $body_row->addCell(
                new Table\Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->translate('never')
                        : date($this->settings['date_format'], $row['expire'])
                )
            );
            if (get_privilages("manage_user_services")) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(false);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }
}
