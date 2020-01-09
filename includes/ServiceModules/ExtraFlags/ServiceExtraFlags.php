<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\UnauthorizedException;
use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\AdminPaymentService;
use App\Payment\BoughtServiceService;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceAvailableOnServers;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\ServiceModules\Interfaces\IServiceServiceCodeAdminManage;
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
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class ServiceExtraFlags extends ServiceModule implements
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
    IServiceServiceCode,
    IServiceServiceCodeAdminManage
{
    const MODULE_ID = "extra_flags";
    const USER_SERVICE_TABLE = "user_service_extra_flags";

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var Path */
    private $path;

    /** @var ServiceDescriptionCreator */
    private $serviceDescriptionCreator;

    /** @var Heart */
    private $heart;

    /** @var Auth */
    private $auth;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var DatabaseLogger */
    private $logger;

    /** @var ExpiredUserServiceService */
    private $expiredUserServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->expiredUserServiceService = $this->app->make(ExpiredUserServiceService::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->path = $this->app->make(Path::class);
        $this->serviceDescriptionCreator = $this->app->make(ServiceDescriptionCreator::class);
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

        $this->serviceDescriptionCreator->create($data['id']);

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

        $table->setDbRowsAmount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

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

    public function purchaseFormValidate($data)
    {
        // Wyłuskujemy taryfę
        $value = explode(';', $data['value']);

        // Pobieramy auth_data
        $authData = $this->getAuthData($data);

        $purchase = new Purchase($this->auth->user());
        $purchase->setOrder([
            'server' => $data['server'],
            'type' => $data['type'],
            'auth_data' => trim($authData),
            'password' => $data['password'],
            'passwordr' => $data['password_repeat'],
        ]);
        $purchase->setTariff($this->heart->getTariff($value[2]));
        $purchase->setEmail($data['email']);

        return $this->purchaseDataValidate($purchase);
    }

    /**
     * @param Purchase $purchaseData
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchaseData)
    {
        $warnings = [];

        // Serwer
        if (!strlen($purchaseData->getOrder('server'))) {
            $warnings['server'][] = $this->lang->t('must_choose_server');
        } else {
            // Sprawdzanie czy serwer o danym id istnieje w bazie
            $server = $this->heart->getServer($purchaseData->getOrder('server'));

            if (!$this->heart->serverServiceLinked($server->getId(), $this->service->getId())) {
                $warnings['server'][] = $this->lang->t('chosen_incorrect_server');
            }
        }

        // Wartość usługi
        if (!$purchaseData->getTariff()) {
            $warnings['value'][] = $this->lang->t('must_choose_amount');
        } else {
            // Wyszukiwanie usługi o konkretnej cenie
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" .
                        TABLE_PREFIX .
                        "pricelist` " .
                        "WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
                    [$this->service->getId(), $purchaseData->getTariff(), $server->getId()]
                )
            );

            if (!$result->rowCount()) {
                // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
                return [
                    'status' => "no_option",
                    'text' => $this->lang->t('service_not_affordable'),
                    'positive' => false,
                ];
            }

            $price = $result->fetch();
        }

        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if (
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_NICK &&
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_IP &&
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_SID
        ) {
            $warnings['type'][] = $this->lang->t('must_choose_type');
        } else {
            if (!($this->service->getTypes() & $purchaseData->getOrder('type'))) {
                $warnings['type'][] = $this->lang->t('chosen_incorrect_type');
            } else {
                if (
                    $purchaseData->getOrder('type') &
                    (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)
                ) {
                    // Nick
                    if ($purchaseData->getOrder('type') == ExtraFlagType::TYPE_NICK) {
                        if (
                            $warning = check_for_warnings(
                                "nick",
                                $purchaseData->getOrder('auth_data')
                            )
                        ) {
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
                                $purchaseData->getOrder('auth_data'),
                                $server->getId(),
                            ]
                        );
                    }
                    // IP
                    else {
                        if ($purchaseData->getOrder('type') == ExtraFlagType::TYPE_IP) {
                            if (
                                $warning = check_for_warnings(
                                    "ip",
                                    $purchaseData->getOrder('auth_data')
                                )
                            ) {
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
                                    $purchaseData->getOrder('auth_data'),
                                    $server->getId(),
                                ]
                            );
                        }
                    }

                    // Hasło
                    if (
                        $warning = check_for_warnings(
                            "password",
                            $purchaseData->getOrder('password')
                        )
                    ) {
                        $warnings['password'] = array_merge(
                            (array) $warnings['password'],
                            $warning
                        );
                    }
                    if (
                        $purchaseData->getOrder('password') != $purchaseData->getOrder('passwordr')
                    ) {
                        $warnings['password_repeat'][] = $this->lang->t('passwords_not_match');
                    }

                    // Sprawdzanie czy istnieje już taka usługa
                    if ($tmpPassword = $this->db->query($query)->fetchColumn()) {
                        // TODO: Usunąć md5 w przyszłości
                        if (
                            $tmpPassword != $purchaseData->getOrder('password') &&
                            $tmpPassword != md5($purchaseData->getOrder('password'))
                        ) {
                            $warnings['password'][] = $this->lang->t(
                                'existing_service_has_different_password'
                            );
                        }
                    }

                    unset($tmpPassword);
                }
                // SteamID
                else {
                    if (
                        $warning = check_for_warnings("sid", $purchaseData->getOrder('auth_data'))
                    ) {
                        $warnings['sid'] = array_merge((array) $warnings['sid'], $warning);
                    }
                }
            }
        }

        // E-mail
        if (
            (!is_server_platform($purchaseData->user->getPlatform()) ||
                strlen($purchaseData->getEmail())) &&
            ($warning = check_for_warnings("email", $purchaseData->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData->setOrder([
            'amount' => $price['amount'],
            'forever' => $price['amount'] == -1 ? true : false,
        ]);

        if ($server->getSmsPlatformId()) {
            $purchaseData->setPayment([
                'sms_platform' => $server->getSmsPlatformId(),
            ]);
        }

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $server = $this->heart->getServer($purchaseData->getOrder('server'));
        $typeName = $this->getTypeName2($purchaseData->getOrder('type'));

        $password = '';
        if (strlen($purchaseData->getOrder('password'))) {
            $password =
                "<strong>{$this->lang->t('password')}</strong>: " .
                htmlspecialchars($purchaseData->getOrder('password')) .
                "<br />";
        }

        $email = $purchaseData->getEmail() ?: $this->lang->t('none');
        $authData = $purchaseData->getOrder('auth_data');
        $serviceName = $this->service->getName();
        $serverName = $server->getName();
        $amount = !$purchaseData->getOrder('forever')
            ? $purchaseData->getOrder('amount') . " " . $this->service->getTag()
            : $this->lang->t('forever');

        return $this->template->render(
            "services/extra_flags/order_details",
            compact(
                'amount',
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

    public function purchase(Purchase $purchaseData)
    {
        $this->addPlayerFlags(
            $purchaseData->user->getUid(),
            $purchaseData->getOrder('type'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getOrder('password'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('forever')
        );

        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getEmail(),
            [
                'type' => $purchaseData->getOrder('type'),
                'password' => $purchaseData->getOrder('password'),
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
            // Wstawiamy
            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "user_service` (`uid`, `service`, `expire`) " .
                        "VALUES ('%d', '%s', IF('%d' = '1', '-1', UNIX_TIMESTAMP() + '%d')) ",
                    [$uid, $this->service->getId(), $forever, $days * 24 * 60 * 60]
                )
            );
            $userServiceId = $this->db->lastId();

            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` (`us_id`, `server`, `service`, `type`, `auth_data`, `password`) " .
                        "VALUES ('%d', '%d', '%s', '%d', '%s', '%s')",
                    [
                        $userServiceId,
                        $serverId,
                        $this->service->getId(),
                        $type,
                        $authData,
                        $password,
                    ]
                )
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
        foreach ($flags as $flag => $amount) {
            $set .= $this->db->prepare(", `%s` = '%d'", [$flag, $amount]);
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

        // Pobieramy listę serwerów
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
    public function userServiceAdminAdd($data)
    {
        $user = $this->auth->user();

        $warnings = [];

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Sprawdzamy hasło, jeżeli podano nick albo ip
        if (
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            ($warning = check_for_warnings("password", $data['password']))
        ) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }

        // Amount
        if (!$data['forever']) {
            if ($warning = check_for_warnings("number", $data['amount'])) {
                $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
            } else {
                if ($data['amount'] < 0) {
                    $warnings['amount'][] = $this->lang->t('days_quantity_positive');
                }
            }
        }

        // E-mail
        if (strlen($data['email']) && ($warning = check_for_warnings("email", $data['email']))) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Dodajemy usługę

        $paymentId = $this->adminPaymentService->payByAdmin($user);

        // Pobieramy dane o użytkowniku na które jego wykupiona usługa
        $purchaseUser = $this->heart->getUser($data['uid']);
        $purchaseData = new Purchase($purchaseUser);
        $purchaseData->setService($this->service->getId());
        $purchaseData->setPayment([
            'method' => "admin",
            'payment_id' => $paymentId,
        ]);
        $purchaseData->setOrder([
            'server' => $data['server'],
            'type' => $data['type'],
            'auth_data' => trim($data['auth_data']),
            'password' => $data['password'],
            'amount' => $data['amount'],
            'forever' => (bool) $data['forever'],
        ]);
        $purchaseData->setEmail($data['email']);
        $boughtServiceId = $this->purchase($purchaseData);

        $this->logger->logWithActor('log_user_service_added', $boughtServiceId);

        return [
            'status' => "ok",
            'text' => $this->lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function userServiceAdminEditFormGet($userService)
    {
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
                'selected' => $userService['service'] == $service->getId() ? "selected" : "",
            ]);
        }

        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            if ($this->service->getTypes() & $optionId) {
                $types .= create_dom_element("option", $this->getTypeName($optionId), [
                    'value' => $optionId,
                    'selected' => $optionId == $userService['type'] ? "selected" : "",
                ]);
            }
        }

        if ($userService['type'] == ExtraFlagType::TYPE_NICK) {
            $nick = $userService['auth_data'];
            $styles['nick'] = $styles['password'] = "display: table-row-group";
        } elseif ($userService['type'] == ExtraFlagType::TYPE_IP) {
            $ip = $userService['auth_data'];
            $styles['ip'] = $styles['password'] = "display: table-row-group";
        } elseif ($userService['type'] == ExtraFlagType::TYPE_SID) {
            $sid = $userService['auth_data'];
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
                'selected' => $userService['server'] == $server->getId() ? "selected" : "",
            ]);
        }

        // Pobranie hasła
        if (strlen($userService['password'])) {
            $password = "********";
        }

        // Zamiana daty
        if ($userService['expire'] == -1) {
            $checked = "checked";
            $disabled = "disabled";
            $userService['expire'] = "";
        } else {
            $userService['expire'] = date($this->settings->getDateFormat(), $userService['expire']);
        }

        return $this->template->render(
            "services/extra_flags/user_service_admin_edit",
            compact(
                'userService',
                'types',
                'styles',
                'nick',
                'ip',
                'sid',
                'password',
                'services',
                'servers',
                'disabled',
                'checked'
            ) + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    //
    // Funkcja edytowania usługi przez admina z PA
    //
    public function userServiceAdminEdit($data, $userService)
    {
        $warnings = [];

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Expire
        if (!$data['forever'] && ($data['expire'] = strtotime($data['expire'])) === false) {
            $warnings['expire'][] = $this->lang->t('wrong_date_format');
        }
        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($data['password']) &&
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService['password'])
        ) {
            $warnings['password'][] = $this->lang->t('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę
        $editReturn = $this->userServiceEdit($userService, $data);

        if ($editReturn['status'] == 'ok') {
            $this->logger->logWithActor('log_user_service_edited', $userService['id']);
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
            if (!strlen($data['server'])) {
                $warnings['server'][] = $this->lang->t('choose_server_for_service');
            }
            // Wyszukiwanie serwera o danym id
            elseif (($server = $this->heart->getServer($data['server'])) === null) {
                $warnings['server'][] = $this->lang->t('no_server_id');
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }
    }

    public function userServiceDeletePost($userService)
    {
        // Odśwież flagi gracza
        $this->recalculatePlayerFlags(
            $userService['server'],
            $userService['type'],
            $userService['auth_data']
        );
    }

    // ----------------------------------------------------------------------------------
    // ### Edytowanie usług przez użytkownika

    public function userOwnServiceEditFormGet($userService)
    {
        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $serviceInfo = [];
        $styles['nick'] = $styles['ip'] = $styles['sid'] = $styles['password'] = "display: none";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            // Kiedy dana usługa nie wspiera danego typu i wykupiona usługa nie ma tego typu
            if (!($this->service->getTypes() & $optionId) && $optionId != $userService['type']) {
                continue;
            }

            $serviceInfo['types'] .= create_dom_element("option", $this->getTypeName($optionId), [
                'value' => $optionId,
                'selected' => $optionId == $userService['type'] ? "selected" : "",
            ]);

            if ($optionId == $userService['type']) {
                switch ($optionId) {
                    case ExtraFlagType::TYPE_NICK:
                        $serviceInfo['player_nick'] = $userService['auth_data'];
                        $styles['nick'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_IP:
                        $serviceInfo['player_ip'] = $userService['auth_data'];
                        $styles['ip'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_SID:
                        $serviceInfo['player_sid'] = $userService['auth_data'];
                        $styles['sid'] = "display: table-row";
                        break;
                }
            }
        }

        // Hasło
        if (strlen($userService['password']) && $userService['password'] != md5("")) {
            $serviceInfo['password'] = "********";
        }

        // Serwer
        $server = $this->heart->getServer($userService['server']);
        $serviceInfo['server'] = $server->getName();

        // Wygasa
        $serviceInfo['expire'] =
            $userService['expire'] == -1
                ? $this->lang->t('never')
                : date($this->settings->getDateFormat(), $userService['expire']);

        // Usługa
        $serviceInfo['service'] = $this->service->getName();

        return $this->template->render(
            "services/extra_flags/user_own_service_edit",
            compact('serviceInfo', 'styles')
        );
    }

    public function userOwnServiceInfoGet($userService, $buttonEdit)
    {
        $serviceInfo['expire'] =
            $userService['expire'] == -1
                ? $this->lang->t('never')
                : date($this->settings->getDateFormat(), $userService['expire']);
        $server = $this->heart->getServer($userService['server']);
        $serviceInfo['server'] = $server->getName();
        $serviceInfo['service'] = $this->service->getName();
        $serviceInfo['type'] = $this->getTypeName2($userService['type']);

        return $this->template->render(
            "services/extra_flags/user_own_service",
            compact('userService', 'buttonEdit', 'serviceInfo') + [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    public function userOwnServiceEdit(array $data, $userService)
    {
        $warnings = [];

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($data['password']) &&
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService['password'])
        ) {
            $warnings['password'][] = $this->lang->t('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings, false);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę

        $editReturn = $this->userServiceEdit($userService, [
            'type' => $data['type'],
            'auth_data' => $data['auth_data'],
            'password' => $data['password'],
        ]);

        if ($editReturn['status'] == 'ok') {
            $this->logger->logWithActor('log_user_edited_service', $userService['id']);
        }

        return $editReturn;
    }

    // ----------------------------------------------------------------------------------
    // ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

    private function userServiceEdit($userService, $data)
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
                    array_get($data, 'server', $userService['server']),
                    array_get($data, 'type', $userService['type']),
                    array_get($data, 'auth_data', $userService['auth_data']),
                    $userService['id'],
                ]
            )
        );

        // Jeżeli istnieje usługa o identycznych danych jak te, na które będziemy zmieniać obecną usługę
        if ($result->rowCount()) {
            // Pobieramy tę drugą usługę
            $userService2 = $result->fetch();

            if (!isset($data['uid']) && $userService['uid'] != $userService2['uid']) {
                return [
                    'status' => "service_exists",
                    'text' => $this->lang->t('service_isnt_yours'),
                    'positive' => false,
                ];
            }

            // Usuwamy opcję którą aktualizujemy
            $this->db->query(
                $this->db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "user_service` " . "WHERE `id` = '%d'",
                    [$userService['id']]
                )
            );

            // Dodajemy expire
            if (!$data['forever'] && isset($data['expire'])) {
                $set[] = [
                    'column' => 'expire',
                    'value' => "( `expire` - UNIX_TIMESTAMP() + '%d' )",
                    'data' => [array_get($data, 'expire', $userService['expire'])],
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

            if (isset($data['server'])) {
                $set[] = [
                    'column' => 'server',
                    'value' => "'%d'",
                    'data' => [$data['server']],
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

            $affected = $this->updateUserService($set, $userService['id'], $userService['id']);
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
                        array_get($data, 'server', $userService['server']),
                        array_get($data, 'type', $userService['type']),
                        array_get($data, 'auth_data', $userService['auth_data']),
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
            $userService['server'],
            $userService['type'],
            $userService['auth_data']
        );

        // Odśwież flagi gracza ( już po edycji )
        $this->recalculatePlayerFlags(
            array_get($data, 'server', $userService['server']),
            array_get($data, 'type', $userService['type']),
            array_get($data, 'auth_data', $userService['auth_data'])
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

    public function serviceTakeOver($data)
    {
        $user = $this->auth->user();

        // Serwer
        if (!strlen($data['server'])) {
            $warnings['server'][] = $this->lang->t('field_no_empty');
        }

        // Typ
        if (!strlen($data['type'])) {
            $warnings['type'][] = $this->lang->t('field_no_empty');
        }

        switch ($data['type']) {
            case "1":
                // Nick
                if (!strlen($data['nick'])) {
                    $warnings['nick'][] = $this->lang->t('field_no_empty');
                }

                // Hasło
                if (!strlen($data['password'])) {
                    $warnings['password'][] = $this->lang->t('field_no_empty');
                }

                $authData = $data['nick'];
                break;

            case "2":
                // IP
                if (!strlen($data['ip'])) {
                    $warnings['ip'][] = $this->lang->t('field_no_empty');
                }

                // Hasło
                if (!strlen($data['password'])) {
                    $warnings['password'][] = $this->lang->t('field_no_empty');
                }

                $authData = $data['ip'];
                break;

            case "4":
                // SID
                if (!strlen($data['sid'])) {
                    $warnings['sid'][] = $this->lang->t('field_no_empty');
                }

                $authData = $data['sid'];
                break;
        }

        // Płatność
        if (!strlen($data['payment'])) {
            $warnings['payment'][] = $this->lang->t('field_no_empty');
        }

        if (in_array($data['payment'], [Purchase::METHOD_SMS, Purchase::METHOD_TRANSFER])) {
            if (!strlen($data['payment_id'])) {
                $warnings['payment_id'][] = $this->lang->t('field_no_empty');
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        if ($data['payment'] == Purchase::METHOD_TRANSFER) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                        "WHERE t.payment = 'transfer' AND t.payment_id = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                    [$data['payment_id'], $this->service->getId(), $data['server'], $authData]
                )
            );

            if (!$result->rowCount()) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->t('no_user_service'),
                    'positive' => false,
                ];
            }
        } elseif ($data['payment'] == Purchase::METHOD_SMS) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                        "WHERE t.payment = 'sms' AND t.sms_code = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                    [$data['payment_id'], $this->service->getId(), $data['server'], $authData]
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
                "SELECT `id` FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE us.service = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `password` = '%s' OR `password` = '%s' )",
                [
                    $this->service->getId(),
                    $data['server'],
                    $data['type'],
                    $authData,
                    $data['password'],
                    md5($data['password']),
                ]
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

        $statement = $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET `uid` = '%d' " .
                    "WHERE `id` = '%d'",
                [$user->getUid(), $row['id']]
            )
        );

        if (!$statement->rowCount()) {
            return [
                'status' => "service_not_taken_over",
                'text' => $this->lang->t('service_not_taken_over'),
                'positive' => false,
            ];
        }

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
     * @param integer $serverId
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
     * Funkcja zwraca listę dostępnych taryf danej usługi na danym serwerze
     *
     * @param integer $serverId
     *
     * @return string
     */
    private function tariffs_for_server($serverId)
    {
        $server = $this->heart->getServer($serverId);
        $smsPlatformId = $server->getSmsPlatformId() ?: $this->settings->getSmsPlatformId();
        $paymentPlatform = $this->paymentPlatformRepository->get($smsPlatformId);
        $paymentModule = $paymentPlatform ? $paymentPlatform->getModuleId() : '';

        // Pobieranie kwot za które można zakupić daną usługę na danym serwerze
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.id AS `tariff`, p.amount AS `amount` " .
                    "FROM `" .
                    TABLE_PREFIX .
                    "pricelist` AS p " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    "tariffs` AS t ON t.id = p.tariff " .
                    "LEFT JOIN `" .
                    TABLE_PREFIX .
                    "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
                    "WHERE p.service = '%s' AND ( p.server = '%d' OR p.server = '-1' ) " .
                    "ORDER BY t.provision ASC",
                [$paymentModule, $this->service->getId(), $serverId]
            )
        );

        $values = '';
        foreach ($result as $row) {
            $provision = number_format($row['provision'] / 100, 2);
            $smsCost = strlen($row['sms_number'])
                ? number_format(
                    (get_sms_cost($row['sms_number']) / 100) * $this->settings->getVat(),
                    2
                )
                : 0;
            $amount =
                $row['amount'] != -1
                    ? "{$row['amount']} {$this->service->getTag()}"
                    : $this->lang->t('forever');
            $values .= $this->template->render(
                "services/extra_flags/purchase_value",
                compact('provision', 'smsCost', 'row', 'amount'),
                true,
                false
            );
        }

        return $this->template->render(
            "services/extra_flags/tariffs_for_server",
            compact('values')
        );
    }

    public function actionExecute($action, $data)
    {
        switch ($action) {
            case "tariffs_for_server":
                return $this->tariffs_for_server(intval($data['server']));
            case "servers_for_service":
                return $this->serversForService(intval($data['server']));
            default:
                return '';
        }
    }

    public function serviceCodeValidate(Purchase $purchaseData, $code)
    {
        return true;
    }

    public function serviceCodeAdminAddFormGet()
    {
        // Pobieramy listę serwerów
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
            "services/extra_flags/service_code_admin_add",
            compact('servers') + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    public function serviceCodeAdminAddValidate($data)
    {
        $warnings = [];

        // Serwer
        if (!strlen($data['server'])) {
            $warnings['server'][] = $this->lang->t('have_to_choose_server');
        }
        // Wyszukiwanie serwera o danym id
        elseif (($server = $this->heart->getServer($data['server'])) === null) {
            $warnings['server'][] = $this->lang->t('no_server_id');
        }

        // Taryfa
        $tariff = explode(';', $data['amount']);
        $tariff = $tariff[2];
        if (!strlen($data['amount'])) {
            $warnings['amount'][] = $this->lang->t('must_choose_quantity');
        } elseif ($this->heart->getTariff($tariff) === null) {
            $warnings['amount'][] = $this->lang->t('no_such_tariff');
        }

        return $warnings;
    }

    public function serviceCodeAdminAddInsert($data)
    {
        $tariff = explode(';', $data['amount']);
        $tariff = $tariff[2];

        return [
            'tariff' => $tariff,
            'server' => $data['server'],
        ];
    }

    // Zwraca wartość w zależności od typu
    private function getAuthData($data)
    {
        if ($data['type'] == ExtraFlagType::TYPE_NICK) {
            return $data['nick'];
        }

        if ($data['type'] == ExtraFlagType::TYPE_IP) {
            return $data['ip'];
        }

        if ($data['type'] == ExtraFlagType::TYPE_SID) {
            return $data['sid'];
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
