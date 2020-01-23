<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\UnauthorizedException;
use App\Loggers\DatabaseLogger;
use App\Models\ExtraFlagsUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\UserService;
use App\Payment\AdminPaymentService;
use App\Payment\BoughtServiceService;
use App\Payment\PurchasePriceService;
use App\Payment\PurchaseValidationService;
use App\Repositories\PriceRepository;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceAvailableOnServers;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\ServiceModules\ServiceModule;
use App\Services\ExpiredUserServiceService;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Renders\PurchasePriceRenderer;
use UnexpectedValueException;

class ExtraFlagsServiceModule extends ServiceModule implements
    IServiceAdminManage,
    IServiceCreate,
    IServiceAvailableOnServers,
    IServiceUserServiceAdminDisplay,
    IServicePurchase,
    IServicePurchaseWeb,
    IServicePurchaseOutside,
    IServiceUserServiceAdminAdd,
    IServiceUserServiceAdminEdit,
    IServiceActionExecute,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceTakeOver,
    IServiceServiceCode
{
    const MODULE_ID = "extra_flags";
    const USER_SERVICE_TABLE = "user_service_extra_flags";

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    /** @var Auth */
    private $auth;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var DatabaseLogger */
    private $logger;

    /** @var ExpiredUserServiceService */
    private $expiredUserServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var PurchasePriceService */
    private $purchasePriceService;

    /** @var PurchasePriceRenderer */
    private $purchasePriceRenderer;

    /** @var PurchaseValidationService */
    private $purchaseValidationService;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->expiredUserServiceService = $this->app->make(ExpiredUserServiceService::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        $this->priceRepository = $this->app->make(PriceRepository::class);
        $this->purchasePriceService = $this->app->make(PurchasePriceService::class);
        $this->purchasePriceRenderer = $this->app->make(PurchasePriceRenderer::class);
        $this->purchaseValidationService = $this->app->make(PurchaseValidationService::class);
        $this->userServiceRepository = $this->app->make(UserServiceRepository::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
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
            $warnings['web'][] = $this->lang->t('only_yes_no');
        }

        // Flagi
        if (!strlen($flags)) {
            $warnings['flags'][] = $this->lang->t('field_no_empty');
        } elseif (strlen($flags) > 25) {
            $warnings['flags'][] = $this->lang->t('too_many_flags');
        } elseif (implode('', array_unique(str_split($flags))) != $flags) {
            $warnings['flags'][] = $this->lang->t('same_flags');
        }

        // Typy
        if (empty($types)) {
            $warnings['type[]'][] = $this->lang->t('no_type_chosen');
        }

        // Sprawdzamy, czy typy są prawidłowe
        foreach ($types as $type) {
            if (
                !(
                    $type &
                    (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID)
                )
            ) {
                $warnings['type[]'][] = $this->lang->t('wrong_type_chosen');
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

        $this->serviceDescriptionService->create($data['id']);

        return [
            'types' => $types,
            'flags' => $data['flags'],
            'data' => $extraData,
        ];
    }

    // ----------------------------------------------------------------------------------
    // ### Wyświetlanie usług użytkowników w PA

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->t('extra_flags');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        $pageNumber = $currentPage->getPageNumber();

        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(
            new HeadCell("{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}")
        );
        $table->addHeadCell(new HeadCell($this->lang->t('expires')));

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

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid'] ? "{$row['username']} ({$row['uid']})" : $this->lang->t('none')
                )
            );
            $bodyRow->addCell(new Cell($row['server']));
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['auth_data']));
            $bodyRow->addCell(
                new Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->t('never')
                        : date($this->settings->getDateFormat(), $row['expire'])
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

    public function purchaseFormGet(array $query)
    {
        $heart = $this->heart;
        $user = $this->auth->user();

        // Generujemy typy usługi
        $types = "";
        for ($i = 0, $value = 1; $i < 3; $value = 1 << ++$i) {
            if ($this->service->getTypes() & $value) {
                $type = ExtraFlagType::getTypeName($value);
                $types .= $this->template->render(
                    "services/extra_flags/service_type",
                    compact('value', 'type')
                );
            }
        }

        // Pobieranie serwerów na których można zakupić daną usługę
        $servers = "";
        foreach ($heart->getServers() as $id => $server) {
            // Usługi nie mozna kupic na tym serwerze
            if (!$heart->serverServiceLinked($id, $this->service->getId())) {
                continue;
            }

            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
            ]);
        }

        return $this->template->render(
            "services/extra_flags/purchase_form",
            compact('types', 'user', 'servers') + ['serviceId' => $this->service->getId()]
        );
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $priceId = as_int(array_get($body, 'price_id'));
        $serverId = as_int(array_get($body, 'server_id'));
        $type = as_int(array_get($body, 'type'));
        $password = array_get($body, 'password');
        $passwordRepeat = array_get($body, 'password_repeat');
        $email = array_get($body, 'email');

        $authData = $this->getAuthData($body);
        $price = $this->priceRepository->get($priceId);

        $purchase->setOrder([
            Purchase::ORDER_SERVER => $serverId,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $passwordRepeat,
        ]);
        $purchase->setEmail($email);
        $purchase->setPrice($price);

        return $this->purchaseDataValidate($purchase);
    }

    /**
     * @param Purchase $purchase
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchase)
    {
        $warnings = [];

        if (!strlen($purchase->getOrder(Purchase::ORDER_SERVER))) {
            $warnings['server_id'][] = $this->lang->t('must_choose_server');
        } else {
            $server = $this->heart->getServer($purchase->getOrder(Purchase::ORDER_SERVER));

            if (
                !$server ||
                !$this->heart->serverServiceLinked($server->getId(), $this->service->getId())
            ) {
                $warnings['server_id'][] = $this->lang->t('chosen_incorrect_server');
            } elseif ($server->getSmsPlatformId()) {
                $purchase->setPayment([
                    Purchase::PAYMENT_SMS_PLATFORM => $server->getSmsPlatformId(),
                ]);
            }
        }

        $price = $purchase->getPrice();
        if (!$price) {
            $warnings['price_id'][] = $this->lang->t('must_choose_quantity');
        } elseif (!$this->purchaseValidationService->isPriceAvailable($price, $purchase)) {
            return [
                'status' => "no_option",
                'text' => $this->lang->t('service_not_affordable'),
                'positive' => false,
            ];
        }

        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if (
            $purchase->getOrder('type') != ExtraFlagType::TYPE_NICK &&
            $purchase->getOrder('type') != ExtraFlagType::TYPE_IP &&
            $purchase->getOrder('type') != ExtraFlagType::TYPE_SID
        ) {
            $warnings['type'][] = $this->lang->t('must_choose_type');
        } elseif (!($this->service->getTypes() & $purchase->getOrder('type'))) {
            $warnings['type'][] = $this->lang->t('chosen_incorrect_type');
        } elseif (
            $purchase->getOrder('type') &
            (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)
        ) {
            // Nick
            if ($purchase->getOrder('type') == ExtraFlagType::TYPE_NICK) {
                if ($warning = check_for_warnings("nick", $purchase->getOrder('auth_data'))) {
                    $warnings['nick'] = array_merge((array) $warnings['nick'], $warning);
                }

                // Sprawdzanie czy istnieje już taka usługa
                $query = $this->db->prepare(
                    "SELECT `password` FROM `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                    [
                        ExtraFlagType::TYPE_NICK,
                        $purchase->getOrder('auth_data'),
                        isset($server) ? $server->getId() : 0,
                    ]
                );
            }
            // IP
            elseif ($purchase->getOrder('type') == ExtraFlagType::TYPE_IP) {
                if ($warning = check_for_warnings("ip", $purchase->getOrder('auth_data'))) {
                    $warnings['ip'] = array_merge((array) $warnings['ip'], $warning);
                }

                // Sprawdzanie czy istnieje już taka usługa
                $query = $this->db->prepare(
                    "SELECT `password` FROM `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                    [
                        ExtraFlagType::TYPE_IP,
                        $purchase->getOrder('auth_data'),
                        isset($server) ? $server->getId() : 0,
                    ]
                );
            }

            // Hasło
            if ($warning = check_for_warnings("password", $purchase->getOrder('password'))) {
                $warnings['password'] = array_merge((array) $warnings['password'], $warning);
            }
            if ($purchase->getOrder('password') != $purchase->getOrder('passwordr')) {
                $warnings['password_repeat'][] = $this->lang->t('passwords_not_match');
            }

            // Sprawdzanie czy istnieje już taka usługa
            if ($tmpPassword = $this->db->query($query)->fetchColumn()) {
                // TODO: Usunąć md5 w przyszłości
                if (
                    $tmpPassword != $purchase->getOrder('password') &&
                    $tmpPassword != md5($purchase->getOrder('password'))
                ) {
                    $warnings['password'][] = $this->lang->t(
                        'existing_service_has_different_password'
                    );
                }
            }

            unset($tmpPassword);
        }
        // SteamID
        elseif ($warning = check_for_warnings("sid", $purchase->getOrder('auth_data'))) {
            $warnings['sid'] = array_merge((array) $warnings['sid'], $warning);
        }

        // E-mail
        if (
            (!is_server_platform($purchase->user->getPlatform()) ||
                strlen($purchase->getEmail())) &&
            ($warning = check_for_warnings("email", $purchase->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
        ];
    }

    public function orderDetails(Purchase $purchase)
    {
        $server = $this->heart->getServer($purchase->getOrder(Purchase::ORDER_SERVER));
        $typeName = $this->getTypeName2($purchase->getOrder('type'));

        $password = '';
        if (strlen($purchase->getOrder('password'))) {
            $password =
                "<strong>{$this->lang->t('password')}</strong>: " .
                htmlspecialchars($purchase->getOrder('password')) .
                "<br />";
        }

        $email = $purchase->getEmail() ?: $this->lang->t('none');
        $authData = $purchase->getOrder('auth_data');
        $serviceName = $this->service->getName();
        $serverName = $server->getName();
        $quantity = $purchase->getOrder(Purchase::ORDER_FOREVER)
            ? $this->lang->t('forever')
            : $purchase->getOrder(Purchase::ORDER_QUANTITY) . " " . $this->service->getTag();

        return $this->template->render(
            "services/extra_flags/order_details",
            compact(
                'quantity',
                'typeName',
                'authData',
                'password',
                'email',
                'serviceName',
                'serverName'
            ),
            true,
            false
        );
    }

    public function purchase(Purchase $purchase)
    {
        $this->addPlayerFlags(
            $purchase->user->getUid(),
            $purchase->getOrder('type'),
            $purchase->getOrder('auth_data'),
            $purchase->getOrder('password'),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->getOrder(Purchase::ORDER_FOREVER)
        );

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder('auth_data'),
            $purchase->getEmail(),
            [
                'type' => $purchase->getOrder('type'),
                'password' => $purchase->getOrder('password'),
            ]
        );
    }

    private function addPlayerFlags(
        $uid,
        $type,
        $authData,
        $password,
        $days,
        $serverId,
        $forever = false
    ) {
        $authData = trim($authData);

        // Usunięcie przestarzałych usług gracza
        $this->expiredUserServiceService->deleteExpiredUserServices();

        // Usunięcie przestarzałych flag graczy
        // Tak jakby co
        $this->deleteOldFlags();

        // Dodajemy usługę gracza do listy usług
        // Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `us_id` FROM `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$this->service->getId(), $serverId, $type, $authData]
            )
        );

        if ($result->rowCount()) {
            // Aktualizujemy
            $row = $result->fetch();
            $userServiceId = $row['us_id'];

            $this->updateUserService(
                [
                    [
                        'column' => 'uid',
                        'value' => "'%d'",
                        'data' => [$uid],
                    ],
                    [
                        'column' => 'password',
                        'value' => "'%s'",
                        'data' => [$password],
                    ],
                    [
                        'column' => 'expire',
                        'value' => "IF('%d' = '1', -1, `expire` + '%d')",
                        'data' => [$forever, $days * 24 * 60 * 60],
                    ],
                ],
                $userServiceId,
                $userServiceId
            );
        } else {
            $this->userServiceRepository->createExtraFlags(
                $this->service->getId(),
                $uid,
                $forever,
                $days,
                $serverId,
                $type,
                $authData,
                $password
            );
        }

        // Ustawiamy jednakowe hasła dla wszystkich usług tego gracza na tym serwerze
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "SET `password` = '%s' " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$password, $serverId, $type, $authData]
            )
        );

        // Przeliczamy flagi gracza, ponieważ dodaliśmy nową usługę
        $this->recalculatePlayerFlags($serverId, $type, $authData);
    }

    private function deleteOldFlags()
    {
        $this->db->query(
            "DELETE FROM `" .
                TABLE_PREFIX .
                "players_flags` " .
                "WHERE (`a` < UNIX_TIMESTAMP() AND `a` != '-1') " .
                "AND (`b` < UNIX_TIMESTAMP() AND `b` != '-1') " .
                "AND (`c` < UNIX_TIMESTAMP() AND `c` != '-1') " .
                "AND (`d` < UNIX_TIMESTAMP() AND `d` != '-1') " .
                "AND (`e` < UNIX_TIMESTAMP() AND `e` != '-1') " .
                "AND (`f` < UNIX_TIMESTAMP() AND `f` != '-1') " .
                "AND (`g` < UNIX_TIMESTAMP() AND `g` != '-1') " .
                "AND (`h` < UNIX_TIMESTAMP() AND `h` != '-1') " .
                "AND (`i` < UNIX_TIMESTAMP() AND `i` != '-1') " .
                "AND (`j` < UNIX_TIMESTAMP() AND `j` != '-1') " .
                "AND (`k` < UNIX_TIMESTAMP() AND `k` != '-1') " .
                "AND (`l` < UNIX_TIMESTAMP() AND `l` != '-1') " .
                "AND (`m` < UNIX_TIMESTAMP() AND `m` != '-1') " .
                "AND (`n` < UNIX_TIMESTAMP() AND `n` != '-1') " .
                "AND (`o` < UNIX_TIMESTAMP() AND `o` != '-1') " .
                "AND (`p` < UNIX_TIMESTAMP() AND `p` != '-1') " .
                "AND (`q` < UNIX_TIMESTAMP() AND `q` != '-1') " .
                "AND (`r` < UNIX_TIMESTAMP() AND `r` != '-1') " .
                "AND (`s` < UNIX_TIMESTAMP() AND `s` != '-1') " .
                "AND (`t` < UNIX_TIMESTAMP() AND `t` != '-1') " .
                "AND (`u` < UNIX_TIMESTAMP() AND `u` != '-1') " .
                "AND (`v` < UNIX_TIMESTAMP() AND `v` != '-1') " .
                "AND (`w` < UNIX_TIMESTAMP() AND `w` != '-1') " .
                "AND (`x` < UNIX_TIMESTAMP() AND `x` != '-1') " .
                "AND (`y` < UNIX_TIMESTAMP() AND `y` != '-1') " .
                "AND (`z` < UNIX_TIMESTAMP() AND `z` != '-1')"
        );
    }

    private function recalculatePlayerFlags($serverId, $type, $authData)
    {
        // Musi byc podany typ, bo inaczej nam wywali wszystkie usługi bez typu
        // Bez serwera oraz auth_data, skrypt po prostu nic nie zrobi
        if (!$type) {
            return;
        }

        // Usuwanie danych z bazy players_flags
        // Ponieważ za chwilę będziemy je tworzyć na nowo
        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" .
                    TABLE_PREFIX .
                    "players_flags` " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$serverId, $type, $authData]
            )
        );

        // Pobieranie wszystkich usług na konkretne dane
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )",
                [$serverId, $type, $authData]
            )
        );

        // Wyliczanie za jaki czas dana flaga ma wygasnąć
        $flags = [];
        $password = "";
        foreach ($result as $row) {
            // Pobranie hasła, bierzemy je tylko raz na początku
            $password = $password ? $password : $row['password'];

            $service = $this->heart->getService($row['service']);
            $serviceFlags = $service->getFlags();
            for ($i = 0; $i < strlen($serviceFlags); ++$i) {
                // Bierzemy maksa, ponieważ inaczej robią się problemy.
                // A tak to jak wygaśnie jakaś usługa, to wykona się cron, usunie ją i przeliczy flagi jeszcze raz
                // I znowu weźmie maksa
                // Czyli stan w tabeli players flags nie jest do końca odzwierciedleniem rzeczywistości :)
                $flags[$serviceFlags[$i]] = $this->maxMinus(
                    array_get($flags, $serviceFlags[$i]),
                    $row['expire']
                );
            }
        }

        // Formowanie flag do zapytania
        $set = '';
        foreach ($flags as $flag => $quantity) {
            $set .= $this->db->prepare(", `%s` = '%d'", [$flag, $quantity]);
        }

        // Dodanie flag
        if (strlen($set)) {
            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "players_flags` " .
                        "SET `server` = '%d', `type` = '%d', `auth_data` = '%s', `password` = '%s'{$set}",
                    [$serverId, $type, $authData, $password]
                )
            );
        }
    }

    public function purchaseInfo($action, array $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $data['extra_data']['type_name'] = $this->getTypeName2($data['extra_data']['type']);

        $password = '';
        if (strlen($data['extra_data']['password'])) {
            $password =
                "<strong>{$this->lang->t('password')}</strong>: " .
                htmlspecialchars($data['extra_data']['password']) .
                "<br />";
        }

        $amount =
            $data['amount'] != -1
                ? "{$data['amount']} {$this->service->getTag()}"
                : $this->lang->t('forever');

        $cost = $data['cost']
            ? number_format($data['cost'] / 100.0, 2) . " " . $this->settings->getCurrency()
            : $this->lang->t('none');

        $data['income'] = number_format($data['income'] / 100.0, 2);

        $server = $this->heart->getServer($data['server']);

        if ($data['extra_data']['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)) {
            $setinfo = $this->lang->t('type_setinfo', $data['extra_data']['password']);
        }

        if ($action == "email") {
            return $this->template->render(
                "services/extra_flags/purchase_info_email",
                compact('data', 'amount', 'password', 'setinfo') + [
                    'serviceName' => $this->service->getName(),
                    'serverName' => $server->getName(),
                ],
                true,
                false
            );
        }

        if ($action == "web") {
            return $this->template->render(
                "services/extra_flags/purchase_info_web",
                compact('cost', 'amount', 'data', 'password', 'setinfo') + [
                    'serviceName' => $this->service->getName(),
                    'serverName' => $server->getName(),
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => ($output = $this->lang->t(
                    'service_was_bought',
                    $this->service->getName(),
                    $server->getName()
                )),
                'class' => "outcome",
            ];
        }

        return '';
    }

    // ----------------------------------------------------------------------------------
    // ### Zarządzanie usługami użytkowników przez admina

    public function userServiceAdminAddFormGet()
    {
        // Pobieramy listę typów usługi, (1<<2) ostatni typ
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            if ($this->service->getTypes() & $optionId) {
                $types .= create_dom_element("option", $this->getTypeName($optionId), [
                    'value' => $optionId,
                ]);
            }
        }

        $servers = "";
        foreach ($this->heart->getServers() as $id => $server) {
            if (!$this->heart->serverServiceLinked($id, $this->service->getId())) {
                continue;
            }

            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
            ]);
        }

        return $this->template->render(
            "services/extra_flags/user_service_admin_add",
            compact('types', 'servers') + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    //
    // Funkcja dodawania usługi przez PA
    //
    public function userServiceAdminAdd(array $body)
    {
        $user = $this->auth->user();

        $body['auth_data'] = trim($this->getAuthData($body));
        $authData = array_get($body, 'auth_data');
        $type = as_int(array_get($body, 'type'));
        $password = array_get($body, 'password');
        $forever = (bool) array_get($body, 'forever');
        $quantity = as_int(array_get($body, 'quantity'));
        $serverId = as_int(array_get($body, 'server_id'));
        $email = array_get($body, 'email');
        $uid = as_int(array_get($body, 'uid'));

        $warnings = [];

        // Sprawdzamy hasło, jeżeli podano nick albo ip
        if (
            $type & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            ($warning = check_for_warnings("password", $password))
        ) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }

        if (!$forever) {
            if ($warning = check_for_warnings("number", $quantity)) {
                $warnings['quantity'] = array_merge((array) $warnings['quantity'], $warning);
            } elseif ($quantity < 0) {
                $warnings['quantity'][] = $this->lang->t('days_quantity_positive');
            }
        }

        // E-mail
        if (strlen($email) && ($warning = check_for_warnings("email", $email))) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($body, $warnings);

        if ($verifyData) {
            return $verifyData;
        }

        //
        // Dodajemy usługę

        $paymentId = $this->adminPaymentService->payByAdmin($user);

        // Pobieramy dane o użytkowniku na które jego wykupiona usługa
        $purchaseUser = $this->heart->getUser($uid);
        $purchase = new Purchase($purchaseUser);
        $purchase->setService($this->service->getId());
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_ADMIN,
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $serverId,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            Purchase::ORDER_QUANTITY => $quantity,
            Purchase::ORDER_FOREVER => $forever,
        ]);
        $purchase->setEmail($email);
        $boughtServiceId = $this->purchase($purchase);

        $this->logger->logWithActor('log_user_service_added', $boughtServiceId);

        return [
            'status' => "ok",
            'text' => $this->lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function userServiceAdminEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        // Pobranie usług
        $services = "";
        foreach ($this->heart->getServices() as $id => $service) {
            $serviceModule = $this->heart->getEmptyServiceModule($service->getModule());
            if ($serviceModule === null) {
                continue;
            }

            // Usługę możemy zmienić tylko na taka, która korzysta z tego samego modułu.
            // Inaczej to nie ma sensu, lepiej ją usunąć i dodać nową
            if ($this->getModuleId() != $serviceModule->getModuleId()) {
                continue;
            }

            $services .= create_dom_element("option", $service->getName(), [
                'value' => $service->getId(),
                'selected' => $userService->getServiceId() === $service->getId() ? "selected" : "",
            ]);
        }

        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            if ($this->service->getTypes() & $optionId) {
                $types .= create_dom_element("option", $this->getTypeName($optionId), [
                    'value' => $optionId,
                    'selected' => $optionId === $userService->getType() ? "selected" : "",
                ]);
            }
        }

        if ($userService->getType() === ExtraFlagType::TYPE_NICK) {
            $nick = $userService->getAuthData();
            $styles['nick'] = $styles['password'] = "display: table-row-group";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_IP) {
            $ip = $userService->getAuthData();
            $styles['ip'] = $styles['password'] = "display: table-row-group";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_SID) {
            $sid = $userService->getAuthData();
            $styles['sid'] = "display: table-row-group";
        }

        // Pobranie serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $id => $server) {
            if (!$this->heart->serverServiceLinked($id, $this->service->getId())) {
                continue;
            }

            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
                'selected' => $userService->getServerId() === $server->getId() ? "selected" : "",
            ]);
        }

        // Pobranie hasła
        if (strlen($userService->getPassword())) {
            $password = "********";
        }

        // Zamiana daty
        $userServiceExpire = '';
        if ($userService->isForever()) {
            $checked = "checked";
            $disabled = "disabled";
        } else {
            $userServiceExpire = convertDate($userService->getExpire());
        }

        return $this->template->renderNoComments(
            "services/extra_flags/user_service_admin_edit",
            compact(
                'types',
                'styles',
                'nick',
                'ip',
                'sid',
                'password',
                'services',
                'servers',
                'disabled',
                'checked',
                'userServiceExpire'
            ) + [
                'moduleId' => $this->getModuleId(),
                'userServiceId' => $userService->getId(),
                'userServiceUid' => $userService->getUid(),
            ]
        );
    }

    public function userServiceAdminEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        $warnings = [];

        $body['auth_data'] = $this->getAuthData($body);
        $forever = (bool) array_get($body, 'forever');
        $expire = array_get($body, 'expire');
        $password = array_get($body, 'password');
        $type = as_int(array_get($body, 'type'));

        // Expire
        if (!$forever && ($expire = strtotime($expire)) === false) {
            $warnings['expire'][] = $this->lang->t('wrong_date_format');
        }
        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($password) &&
            $type & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService->getPassword())
        ) {
            $warnings['password'][] = $this->lang->t('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($body, $warnings);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę
        $editReturn = $this->userServiceEdit($userService, $body);

        if ($editReturn['status'] == 'ok') {
            $this->logger->logWithActor('log_user_service_edited', $userService->getId());
        }

        return $editReturn;
    }

    //
    // Weryfikacja danych przy dodawaniu i przy edycji usługi gracza
    // Zebrane w jednej funkcji, aby nie mnożyć kodu
    //
    private function verifyUserServiceData($data, $warnings, $server = true)
    {
        // ID użytkownika
        if ($data['uid']) {
            if ($warning = check_for_warnings("uid", $data['uid'])) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            } else {
                $editedUser = $this->heart->getUser($data['uid']);
                if (!$editedUser->exists()) {
                    $warnings['uid'][] = $this->lang->t('no_account_id');
                }
            }
        }

        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if (
            $data['type'] != ExtraFlagType::TYPE_NICK &&
            $data['type'] != ExtraFlagType::TYPE_IP &&
            $data['type'] != ExtraFlagType::TYPE_SID
        ) {
            $warnings['type'][] = $this->lang->t('must_choose_service_type');
        } else {
            if (!($this->service->getTypes() & $data['type'])) {
                $warnings['type'][] = $this->lang->t('forbidden_purchase_type');
            } else {
                if ($data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)) {
                    // Nick
                    if (
                        $data['type'] == ExtraFlagType::TYPE_NICK &&
                        ($warning = check_for_warnings("nick", $data['auth_data']))
                    ) {
                        $warnings['nick'] = array_merge((array) $warnings['nick'], $warning);
                    }
                    // IP
                    else {
                        if (
                            $data['type'] == ExtraFlagType::TYPE_IP &&
                            ($warning = check_for_warnings("ip", $data['auth_data']))
                        ) {
                            $warnings['ip'] = array_merge((array) $warnings['ip'], $warning);
                        }
                    }

                    // Hasło
                    if (
                        strlen($data['password']) &&
                        ($warning = check_for_warnings("password", $data['password']))
                    ) {
                        $warnings['password'] = array_merge(
                            (array) $warnings['password'],
                            $warning
                        );
                    }
                }
                // SteamID
                else {
                    if ($warning = check_for_warnings("sid", $data['auth_data'])) {
                        $warnings['sid'] = array_merge((array) $warnings['sid'], $warning);
                    }
                }
            }
        }

        // Server
        if ($server) {
            if (!strlen($data['server_id'])) {
                $warnings['server_id'][] = $this->lang->t('choose_server_for_service');
            }
            // Wyszukiwanie serwera o danym id
            elseif (($server = $this->heart->getServer($data['server_id'])) === null) {
                $warnings['server_id'][] = $this->lang->t('no_server_id');
            }
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        return null;
    }

    public function userServiceDeletePost(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        // Odśwież flagi gracza
        $this->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );
    }

    // ----------------------------------------------------------------------------------
    // ### Edytowanie usług przez użytkownika

    public function userOwnServiceEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $serviceInfo = [];
        $styles['nick'] = $styles['ip'] = $styles['sid'] = $styles['password'] = "display: none";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            // Kiedy dana usługa nie wspiera danego typu i wykupiona usługa nie ma tego typu
            if (!($this->service->getTypes() & $optionId) && $optionId != $userService->getType()) {
                continue;
            }

            $serviceInfo['types'] .= create_dom_element("option", $this->getTypeName($optionId), [
                'value' => $optionId,
                'selected' => $optionId == $userService->getType() ? "selected" : "",
            ]);

            if ($optionId == $userService->getType()) {
                switch ($optionId) {
                    case ExtraFlagType::TYPE_NICK:
                        $serviceInfo['player_nick'] = $userService->getAuthData();
                        $styles['nick'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_IP:
                        $serviceInfo['player_ip'] = $userService->getAuthData();
                        $styles['ip'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_SID:
                        $serviceInfo['player_sid'] = $userService->getAuthData();
                        $styles['sid'] = "display: table-row";
                        break;
                }
            }
        }

        // Hasło
        if (strlen($userService->getPassword()) && $userService->getPassword() != md5("")) {
            $serviceInfo['password'] = "********";
        }

        // Serwer
        $server = $this->heart->getServer($userService->getServerId());
        $serviceInfo['server'] = $server->getName();

        // Wygasa
        $serviceInfo['expire'] = $userService->isForever()
            ? $this->lang->t('never')
            : convertDate($userService->getExpire());

        // Usługa
        $serviceInfo['service'] = $this->service->getName();

        return $this->template->render(
            "services/extra_flags/user_own_service_edit",
            compact('serviceInfo', 'styles')
        );
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        $server = $this->heart->getServer($userService->getServerId());

        return $this->template->render("services/extra_flags/user_own_service", [
            'buttonEdit' => $buttonEdit,
            'authData' => $userService->getAuthData(),
            'userServiceId' => $userService->getId(),
            'expire' => $userService->isForever()
                ? $this->lang->t('never')
                : convertDate($userService->getExpire()),
            'moduleId' => $this->getModuleId(),
            'serverName' => $server->getName(),
            'serviceName' => $this->service->getName(),
            'type' => $this->getTypeName2($userService->getType()),
        ]);
    }

    public function userOwnServiceEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagsUserService)) {
            throw new UnexpectedValueException();
        }

        $warnings = [];

        $body['auth_data'] = $this->getAuthData($body);
        $password = array_get($body, 'password');
        $type = as_int(array_get($body, 'type'));
        $authData = array_get($body, 'auth_data');

        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($password) &&
            $type & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService->getPassword())
        ) {
            $warnings['password'][] = $this->lang->t('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($body, $warnings, false);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę

        $editReturn = $this->userServiceEdit($userService, [
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
        ]);

        if ($editReturn['status'] == 'ok') {
            $this->logger->logWithActor('log_user_edited_service', $userService->getId());
        }

        return $editReturn;
    }

    // ----------------------------------------------------------------------------------
    // ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

    private function userServiceEdit(ExtraFlagsUserService $userService, $data)
    {
        $set = [];
        // Dodanie hasła do zapytania
        if (strlen($data['password'])) {
            $set[] = [
                'column' => 'password',
                'value' => "'%s'",
                'data' => [$data['password']],
            ];
        }

        // Dodajemy uid do zapytania
        if (isset($data['uid'])) {
            $set[] = [
                'column' => 'uid',
                'value' => "'%d'",
                'data' => [$data['uid']],
            ];
        }

        // Dodajemy expire na zawsze
        if ($data['forever']) {
            $set[] = [
                'column' => 'expire',
                'value' => "-1",
            ];
        }

        // Sprawdzenie czy nie ma już takiej usługi
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE us.service = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND `id` != '%d'",
                [
                    $this->service->getId(),
                    array_get($data, 'server', $userService->getServerId()),
                    array_get($data, 'type', $userService->getType()),
                    array_get($data, 'auth_data', $userService->getAuthData()),
                    $userService->getId(),
                ]
            )
        );

        // Jeżeli istnieje usługa o identycznych danych jak te, na które będziemy zmieniać obecną usługę
        if ($result->rowCount()) {
            $userService2 = $result->fetch();

            if (!isset($data['uid']) && $userService->getUid() != $userService2['uid']) {
                return [
                    'status' => "service_exists",
                    'text' => $this->lang->t('service_isnt_yours'),
                    'positive' => false,
                ];
            }

            $this->userServiceRepository->delete($userService->getId());

            // Dodajemy expire
            if (!$data['forever'] && isset($data['expire'])) {
                $set[] = [
                    'column' => 'expire',
                    'value' => "( `expire` - UNIX_TIMESTAMP() + '%d' )",
                    'data' => [array_get($data, 'expire', $userService->getExpire())],
                ];
            }

            // Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
            $affected = $this->updateUserService($set, $userService2['id'], $userService2['id']);
        } else {
            $set[] = [
                'column' => 'service',
                'value' => "'%s'",
                'data' => [$this->service->getId()],
            ];

            if (!$data['forever'] && isset($data['expire'])) {
                $set[] = [
                    'column' => 'expire',
                    'value' => "'%d'",
                    'data' => [$data['expire']],
                ];
            }

            if (isset($data['server_id'])) {
                $set[] = [
                    'column' => 'server',
                    'value' => "'%d'",
                    'data' => [$data['server_id']],
                ];
            }

            if (isset($data['type'])) {
                $set[] = [
                    'column' => 'type',
                    'value' => "'%d'",
                    'data' => [$data['type']],
                ];
            }

            if (isset($data['auth_data'])) {
                $set[] = [
                    'column' => 'auth_data',
                    'value' => "'%s'",
                    'data' => [$data['auth_data']],
                ];
            }

            $affected = $this->updateUserService(
                $set,
                $userService->getId(),
                $userService->getId()
            );
        }

        // Ustaw jednakowe hasła
        // żeby potem nie było problemów z różnymi hasłami
        if (strlen($data['password'])) {
            $this->db->query(
                $this->db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "SET `password` = '%s' " .
                        "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                    [
                        $data['password'],
                        array_get($data, 'server_id', $userService->getServerId()),
                        array_get($data, 'type', $userService->getType()),
                        array_get($data, 'auth_data', $userService->getAuthData()),
                    ]
                )
            );
        }

        // Przelicz flagi tylko wtedy, gdy coś się zmieniło
        if (!$affected) {
            return [
                'status' => "not_edited",
                'text' => $this->lang->t('not_edited_user_service'),
                'positive' => false,
            ];
        }

        // Odśwież flagi gracza ( przed zmiana danych )
        $this->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // Odśwież flagi gracza ( już po edycji )
        $this->recalculatePlayerFlags(
            array_get($data, 'server', $userService->getServerId()),
            array_get($data, 'type', $userService->getType()),
            array_get($data, 'auth_data', $userService->getAuthData())
        );

        return [
            'status' => 'ok',
            'text' => $this->lang->t('edited_user_service'),
            'positive' => true,
        ];
    }

    public function serviceTakeOverFormGet()
    {
        // Generujemy typy usługi
        $types = "";
        for ($i = 0; $i < 3; $i++) {
            $value = 1 << $i;
            if ($this->service->getTypes() & $value) {
                $types .= create_dom_element("option", $this->getTypeName($value), [
                    'value' => $value,
                ]);
            }
        }

        $servers = "";
        // Pobieranie listy serwerów
        foreach ($this->heart->getServers() as $id => $server) {
            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
            ]);
        }

        return $this->template->render(
            "services/extra_flags/service_take_over",
            compact('servers', 'types') + ['moduleId' => $this->getModuleId()]
        );
    }

    public function serviceTakeOver(array $body)
    {
        $user = $this->auth->user();

        $serverId = as_int(array_get($body, 'server_id'));
        $type = as_int(array_get($body, 'type'));
        $nick = array_get($body, 'nick');
        $ip = array_get($body, 'ip');
        $password = array_get($body, 'password');
        $steamId = array_get($body, 'sid');
        $paymentMethod = array_get($body, 'payment_method');
        $paymentId = array_get($body, 'payment_id');

        $warnings = [];

        // Serwer
        if (!strlen($serverId)) {
            $warnings['server_id'][] = $this->lang->t('field_no_empty');
        }

        // Typ
        if (!strlen($type)) {
            $warnings['type'][] = $this->lang->t('field_no_empty');
        }

        switch ($type) {
            case ExtraFlagType::TYPE_NICK:
                if (!strlen($nick)) {
                    $warnings['nick'][] = $this->lang->t('field_no_empty');
                }

                // Hasło
                if (!strlen($password)) {
                    $warnings['password'][] = $this->lang->t('field_no_empty');
                }

                $authData = $nick;
                break;

            case ExtraFlagType::TYPE_IP:
                // IP
                if (!strlen($ip)) {
                    $warnings['ip'][] = $this->lang->t('field_no_empty');
                }

                // Hasło
                if (!strlen($password)) {
                    $warnings['password'][] = $this->lang->t('field_no_empty');
                }

                $authData = $ip;
                break;

            case ExtraFlagType::TYPE_SID:
                // SID
                if (!strlen($steamId)) {
                    $warnings['sid'][] = $this->lang->t('field_no_empty');
                }

                $authData = $steamId;
                break;
        }

        if (!in_array($paymentMethod, [Purchase::METHOD_SMS, Purchase::METHOD_TRANSFER])) {
            $warnings['payment_method'][] = $this->lang->t('field_no_empty');
        }

        if (!strlen($paymentId)) {
            $warnings['payment_id'][] = $this->lang->t('field_no_empty');
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        if ($paymentMethod == Purchase::METHOD_TRANSFER) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                        "WHERE t.payment = 'transfer' AND t.payment_id = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                    [$paymentId, $this->service->getId(), $serverId, $authData]
                )
            );

            if (!$result->rowCount()) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->t('no_user_service'),
                    'positive' => false,
                ];
            }
        } elseif ($paymentMethod == Purchase::METHOD_SMS) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                        "WHERE t.payment = 'sms' AND t.sms_code = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                    [$paymentId, $this->service->getId(), $serverId, $authData]
                )
            );

            if (!$result->rowCount()) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->t('no_user_service'),
                    'positive' => false,
                ];
            }
        }

        // TODO: Usunac md5
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `id` FROM `ss_user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE us.service = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `password` = '%s' OR `password` = '%s' )",
                [$this->service->getId(), $serverId, $type, $authData, $password, md5($password)]
            )
        );

        if (!$result->rowCount()) {
            return [
                'status' => "no_service",
                'text' => $this->lang->t('no_user_service'),
                'positive' => false,
            ];
        }

        $row = $result->fetch();

        $this->userServiceRepository->updateUid($row['id'], $user->getUid());

        return [
            'status' => "ok",
            'text' => $this->lang->t('service_taken_over'),
            'positive' => true,
        ];
    }

    // ----------------------------------------------------------------------------------
    // ### Inne

    /**
     * Metoda zwraca listę serwerów na których można zakupić daną usługę
     *
     * @param int $serverId
     *
     * @return string            Lista serwerów w postaci <option value="id_serwera">Nazwa</option>
     */
    private function serversForService($serverId)
    {
        if (!get_privileges("manage_user_services")) {
            throw new UnauthorizedException();
        }

        $servers = "";
        // Pobieranie serwerów na których można zakupić daną usługę
        foreach ($this->heart->getServers() as $id => $server) {
            if (!$this->heart->serverServiceLinked($id, $this->service->getId())) {
                continue;
            }

            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
                'selected' => $serverId == $server->getId() ? "selected" : "",
            ]);
        }

        return $servers;
    }

    /**
     * Get available prices for given server
     *
     * @param int $serverId
     * @return string
     */
    private function pricesForServer($serverId)
    {
        $server = $this->heart->getServer($serverId);

        $quantities = array_map(function (array $price) {
            return $this->purchasePriceRenderer->render($price, $this->service);
        }, $this->purchasePriceService->getServicePrices($this->service, $server));

        return $this->template->render("services/extra_flags/prices_for_server", [
            'quantities' => implode("", $quantities),
        ]);
    }

    public function actionExecute($action, array $body)
    {
        switch ($action) {
            case "prices_for_server":
                return $this->pricesForServer((int) $body['server_id']);
            case "servers_for_service":
                return $this->serversForService((int) $body['server_id']);
            default:
                return '';
        }
    }

    /**
     * Get value depending on the type
     *
     * @param array $data
     * @return string|null
     */
    private function getAuthData(array $data)
    {
        $type = array_get($data, 'type');

        if ($type == ExtraFlagType::TYPE_NICK) {
            return trim(array_get($data, 'nick'));
        }

        if ($type == ExtraFlagType::TYPE_IP) {
            return trim(array_get($data, 'ip'));
        }

        if ($type == ExtraFlagType::TYPE_SID) {
            return trim(array_get($data, 'sid'));
        }

        return null;
    }

    private function getTypeName($value)
    {
        if ($value == ExtraFlagType::TYPE_NICK) {
            return $this->lang->t('nickpass');
        }

        if ($value == ExtraFlagType::TYPE_IP) {
            return $this->lang->t('ippass');
        }

        if ($value == ExtraFlagType::TYPE_SID) {
            return $this->lang->t('sid');
        }

        return "";
    }

    private function getTypeName2($value)
    {
        if ($value == ExtraFlagType::TYPE_NICK) {
            return $this->lang->t('nick');
        }

        if ($value == ExtraFlagType::TYPE_IP) {
            return $this->lang->t('ip');
        }

        if ($value == ExtraFlagType::TYPE_SID) {
            return $this->lang->t('sid');
        }

        return "";
    }

    private function maxMinus($a, $b)
    {
        if ($a == -1 || $b == -1) {
            return -1;
        }

        return max($a, $b);
    }
}
