<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\DateTimeRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\InArrayRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\PriceAvailableRule;
use App\Http\Validation\Rules\PriceExistsRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServerLinkedToServiceRule;
use App\Http\Validation\Rules\UniqueFlagsRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Rules\YesNoRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PurchasePriceService;
use App\Repositories\PriceRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagAuthDataRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagPasswordDiffersRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagPasswordRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagServiceTypesRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagTypeListRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagTypeRule;
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
use App\Services\PriceTextService;
use App\Support\Expression;
use App\Support\QueryParticle;
use App\System\Auth;
use App\System\Heart;
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
    const USER_SERVICE_TABLE = "ss_user_service_extra_flags";

    /** @var Translator */
    private $lang;

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

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var PlayerFlagService */
    private $playerFlagService;

    /** @var PriceTextService */
    private $priceTextService;

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
        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
        $this->userServiceRepository = $this->app->make(UserServiceRepository::class);
        $this->playerFlagRepository = $this->app->make(PlayerFlagRepository::class);
        $this->transactionRepository = $this->app->make(TransactionRepository::class);
        $this->playerFlagService = $this->app->make(PlayerFlagService::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    /**
     * @param array $data
     * @return ExtraFlagUserService
     */
    public function mapToUserService(array $data)
    {
        return $this->extraFlagUserServiceRepository->mapToModel($data);
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

        return $this->template->renderNoComments(
            "services/extra_flags/extra_fields",
            compact('webSelNo', 'webSelYes', 'types', 'flags') + [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        $validator->extendRules([
            'flags' => [new RequiredRule(), new MaxLengthRule(25), new UniqueFlagsRule()],
            'type' => [new RequiredRule(), new ExtraFlagTypeListRule()],
            'web' => [new RequiredRule(), new YesNoRule()],
        ]);
    }

    public function serviceAdminManagePost(array $body)
    {
        // Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
        $types = 0;
        foreach ($body['type'] as $type) {
            $types |= $type;
        }

        $data = $this->service ? $this->service->getData() : [];
        $data['web'] = $body['web'];

        $this->serviceDescriptionService->create($body['id']);

        return [
            'types' => $types,
            'flags' => $body['flags'],
            'data' => $data,
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

        $queryParticle = new QueryParticle();

        if (isset($query['search'])) {
            $queryParticle->extend(
                create_search_query(
                    ["us.id", "us.uid", "u.username", "srv.name", "s.name", "usef.auth_data"],
                    $query['search']
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS us.id AS `id`, us.uid AS `uid`, u.username AS `username`, " .
                "srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
                "usef.type AS `type`, usef.auth_data AS `auth_data`, us.expire AS `expire` " .
                "FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usef ON usef.us_id = us.id " .
                "LEFT JOIN `ss_services` AS s ON s.id = usef.service " .
                "LEFT JOIN `ss_servers` AS srv ON srv.id = usef.server " .
                "LEFT JOIN `ss_users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(array_merge($queryParticle->params(), get_row_limit($pageNumber)));

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
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
            $bodyRow->addCell(new Cell(convert_expire($row['expire'])));
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
        $authData = trim(array_get($body, 'auth_data'));
        $password = array_get($body, 'password');
        $passwordRepeat = array_get($body, 'password_repeat');
        $email = array_get($body, 'email');

        $price = $this->priceRepository->get($priceId);

        $purchase->setEmail($email);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $serverId,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $passwordRepeat,
        ]);
        if ($price) {
            $purchase->setPrice($price);
        }

        $validator = $this->purchaseDataValidate($purchase);
        $validator->validateOrFail();
    }

    public function purchaseDataValidate(Purchase $purchase)
    {
        $server = $this->heart->getServer($purchase->getOrder(Purchase::ORDER_SERVER));
        $price = $purchase->getPrice();

        if ($server && $server->getSmsPlatformId()) {
            $purchase->setPayment([
                Purchase::PAYMENT_PLATFORM_SMS => $server->getSmsPlatformId(),
            ]);
        }

        return new Validator(
            [
                'auth_data' => $purchase->getOrder('auth_data'),
                'password' => $purchase->getOrder('password'),
                'password_repeat' => $purchase->getOrder('passwordr'),
                'email' => $purchase->getEmail(),
                'price_id' => $price ? $price->getId() : null,
                'server_id' => $purchase->getOrder(Purchase::ORDER_SERVER),
                'type' => $purchase->getOrder('type'),
            ],
            [
                'auth_data' => [new RequiredRule(), new ExtraFlagAuthDataRule()],
                'email' => [
                    is_server_platform($purchase->user->getPlatform()) ? null : new RequiredRule(),
                    new EmailRule(),
                ],
                'password' => [
                    new ExtraFlagPasswordRule(),
                    new PasswordRule(),
                    new ConfirmedRule(),
                    new ExtraFlagPasswordDiffersRule(),
                ],
                'price_id' => [
                    new RequiredRule(),
                    new PriceExistsRule(),
                    new PriceAvailableRule($this->service),
                ],
                'server_id' => [
                    new RequiredRule(),
                    new ServerExistsRule(),
                    new ServerLinkedToServiceRule($this->service),
                ],
                'type' => [
                    new RequiredRule(),
                    new ExtraFlagTypeRule(),
                    new ExtraFlagServiceTypesRule($this->service),
                ],
            ]
        );
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
        $quantity =
            $purchase->getOrder(Purchase::ORDER_QUANTITY) === null
                ? $this->lang->t('forever')
                : $purchase->getOrder(Purchase::ORDER_QUANTITY) . " " . $this->service->getTag();

        return $this->template->renderNoComments(
            "services/extra_flags/order_details",
            compact(
                'quantity',
                'typeName',
                'authData',
                'password',
                'email',
                'serviceName',
                'serverName'
            )
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
            $purchase->getOrder(Purchase::ORDER_SERVER)
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

    private function addPlayerFlags($uid, $type, $authData, $password, $days, $serverId)
    {
        $forever = $days === null;
        $authData = trim($authData);
        $password = strlen($password) ? $password : '';

        // Usunięcie przestarzałych usług gracza
        $this->expiredUserServiceService->deleteExpiredUserServices();

        // Usunięcie przestarzałych flag graczy
        // Tak jakby co
        $this->playerFlagRepository->deleteOldFlags();

        // Dodajemy usługę gracza do listy usług
        // Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
        $statement = $this->db->statement(
            "SELECT * FROM `{$this->getUserServiceTable()}` " .
                "WHERE `service` = ? AND `server` = ? AND `type` = ? AND `auth_data` = ?"
        );
        $statement->execute([$this->service->getId(), $serverId, $type, $authData]);

        if ($statement->rowCount()) {
            $row = $statement->fetch();
            $userServiceId = $row['us_id'];
            $seconds = $days * 24 * 60 * 60;

            $this->userServiceRepository->updateWithModule($this, $userServiceId, [
                'uid' => $uid,
                'password' => $password,
                'expire' => $forever ? null : new Expression("`expire` + $seconds"),
            ]);
        } else {
            $this->extraFlagUserServiceRepository->create(
                $this->service->getId(),
                $uid,
                $forever ? null : $days * 24 * 60 * 60,
                $serverId,
                $type,
                $authData,
                $password
            );
        }

        // Ustawiamy jednakowe hasła dla wszystkich usług tego gracza na tym serwerze
        $this->db
            ->statement(
                "UPDATE `{$this->getUserServiceTable()}` " .
                    "SET `password` = ? " .
                    "WHERE `server` = ? AND `type` = ? AND `auth_data` = ?"
            )
            ->execute([$password, $serverId, $type, $authData]);

        // Przeliczamy flagi gracza, ponieważ dodaliśmy nową usługę
        $this->playerFlagService->recalculatePlayerFlags($serverId, $type, $authData);
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $password = "";
        if (strlen($transaction->getExtraDatum('password'))) {
            $password =
                "<strong>{$this->lang->t('password')}</strong>: " .
                htmlspecialchars($transaction->getExtraDatum('password')) .
                "<br />";
        }

        $quantity =
            $transaction->getQuantity() != -1
                ? "{$transaction->getQuantity()} {$this->service->getTag()}"
                : $this->lang->t('forever');

        $cost = $transaction->getCost()
            ? $this->priceTextService->getPriceText($transaction->getCost())
            : $this->lang->t('none');

        $server = $this->heart->getServer($transaction->getServerId());

        $setinfo = "";
        if (
            $transaction->getExtraDatum('type') &
            (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)
        ) {
            $setinfo = $this->lang->t('type_setinfo', $transaction->getExtraDatum('password'));
        }

        if ($action === "email") {
            return $this->template->renderNoComments(
                "services/extra_flags/purchase_info_email",
                compact('quantity', 'password', 'setinfo') + [
                    'authData' => $transaction->getAuthData(),
                    'typeName' => $this->getTypeName2($transaction->getExtraDatum('type')),
                    'serviceName' => $this->service->getName(),
                    'serverName' => $server ? $server->getName() : 'n/a',
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "services/extra_flags/purchase_info_web",
                compact('cost', 'quantity', 'password', 'setinfo') + [
                    'authData' => $transaction->getAuthData(),
                    'email' => $transaction->getEmail(),
                    'typeName' => $this->getTypeName2($transaction->getExtraDatum('type')),
                    'serviceName' => $this->service->getName(),
                    'serverName' => $server ? $server->getName() : 'n/a',
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                'text' => $this->lang->t(
                    'service_was_bought',
                    $this->service->getName(),
                    $server->getName()
                ),
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

        return $this->template->renderNoComments(
            "services/extra_flags/user_service_admin_add",
            compact('types', 'servers') + ['moduleId' => $this->getModuleId()]
        );
    }

    public function userServiceAdminAdd(array $body)
    {
        $forever = (bool) array_get($body, 'forever');

        $validator = new Validator(
            array_merge($body, [
                'quantity' => as_int(array_get($body, 'quantity')),
                'server_id' => as_int(array_get($body, 'server_id')),
                'uid' => as_int(array_get($body, 'uid')),
            ]),
            [
                'email' => [new EmailRule()],
                'password' => [new ExtraFlagPasswordRule()],
                'quantity' => $forever
                    ? []
                    : [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
                'server_id' => [new RequiredRule(), new ServerExistsRule()],
                'uid' => [new UserExistsRule()],
            ]
        );
        $this->verifyUserServiceData($validator);
        $validated = $validator->validateOrFail();

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin($admin);

        $purchasingUser = $this->heart->getUser($validated['uid']);
        $purchase = new Purchase($purchasingUser);
        $purchase->setServiceId($this->service->getId());
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_ADMIN,
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $validated['server_id'],
            'type' => $validated['type'],
            'auth_data' => $validated['auth_data'],
            'password' => $validated['password'],
            Purchase::ORDER_QUANTITY => $forever ? null : $validated['quantity'],
        ]);
        $purchase->setEmail($validated['email']);
        $boughtServiceId = $this->purchase($purchase);

        $this->logger->logWithActor('log_user_service_added', $boughtServiceId);
    }

    public function userServiceAdminEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
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

        $styles = [
            "nick" => "",
            "ip" => "",
            "sid" => "",
            "password" => "",
        ];

        $disabled = [
            "nick" => "disabled",
            "ip" => "disabled",
            "sid" => "disabled",
            "password" => "disabled",
            "expire" => "",
        ];

        $checked = [
            "forever" => "",
        ];

        if ($userService->getType() === ExtraFlagType::TYPE_NICK) {
            $nick = $userService->getAuthData();
            $styles['nick'] = $styles['password'] = "display: table-row-group";
            $disabled['nick'] = $disabled['password'] = "";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_IP) {
            $ip = $userService->getAuthData();
            $styles['ip'] = $styles['password'] = "display: table-row-group";
            $disabled['ip'] = $disabled['password'] = "";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_SID) {
            $sid = $userService->getAuthData();
            $styles['sid'] = "display: table-row-group";
            $disabled['sid'] = "";
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
            $checked['forever'] = "checked";
            $disabled['expire'] = "disabled";
        } else {
            $userServiceExpire = convert_date($userService->getExpire());
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
                'userServiceUid' => $userService->getUid() ?: "",
            ]
        );
    }

    public function userServiceAdminEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $forever = (bool) array_get($body, 'forever');

        $validator = new Validator(
            array_merge($body, [
                'server_id' => as_int(array_get($body, 'server_id')),
                'uid' => as_int(array_get($body, 'uid')),
            ]),
            [
                'expire' => $forever ? [] : [new RequiredRule(), new DateTimeRule()],
                'server_id' => [new RequiredRule(), new ServerExistsRule()],
                'uid' => [new UserExistsRule()],
            ]
        );
        $this->verifyUserServiceData($validator);

        $validated = $validator->validateOrFail();
        // We need to convert date since it is accepted as a strin value.
        // DateTimeRule protects us from unparsable string
        $validated["expire"] = $forever ? null : strtotime($validated["expire"]);

        $result = $this->userServiceEdit($userService, $validated);

        if ($result) {
            $this->logger->logWithActor('log_user_service_edited', $userService->getId());
        }

        return $result;
    }

    private function verifyUserServiceData(Validator $validator)
    {
        $validator->extendData([
            'auth_data' => trim($validator->getData('auth_data')),
            'type' => as_int($validator->getData('type')),
        ]);

        $validator->extendRules([
            'auth_data' => [new RequiredRule(), new ExtraFlagAuthDataRule()],
            'password' => [],
            'type' => [
                new RequiredRule(),
                new ExtraFlagTypeRule(),
                new ExtraFlagServiceTypesRule($this->service),
            ],
        ]);
    }

    public function userServiceDeletePost(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        // Odśwież flagi gracza
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );
    }

    // ----------------------------------------------------------------------------------
    // ### Edytowanie usług przez użytkownika

    public function userOwnServiceEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $serviceInfo = [
            "types" => "",
            "player_nick" => "",
            "player_ip" => "",
            "player_sid" => "",
            "password" => "",
        ];
        $styles = [
            "nick" => "display: none",
            "ip" => "display: none",
            "sid" => "display: none",
            "password" => "display: none",
        ];
        $disabled = [
            "nick" => "disabled",
            "ip" => "disabled",
            "sid" => "disabled",
            "password" => "disabled",
        ];

        // Dodajemy typ uslugi, (1<<2) ostatni typ
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
                        $disabled['nick'] = $disabled['password'] = "";
                        break;

                    case ExtraFlagType::TYPE_IP:
                        $serviceInfo['player_ip'] = $userService->getAuthData();
                        $styles['ip'] = $styles['password'] = "display: table-row";
                        $disabled['ip'] = $disabled['password'] = "";
                        break;

                    case ExtraFlagType::TYPE_SID:
                        $serviceInfo['player_sid'] = $userService->getAuthData();
                        $styles['sid'] = "display: table-row";
                        $disabled['sid'] = "";
                        break;
                }
            }
        }

        if (strlen($userService->getPassword()) && $userService->getPassword() != md5("")) {
            $serviceInfo['password'] = "********";
        }

        $server = $this->heart->getServer($userService->getServerId());
        $serviceInfo['server'] = $server->getName();
        $serviceInfo['expire'] = convert_expire($userService->getExpire());
        $serviceInfo['service'] = $this->service->getName();

        return $this->template->render(
            "services/extra_flags/user_own_service_edit",
            compact('serviceInfo', 'disabled', 'styles')
        );
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $server = $this->heart->getServer($userService->getServerId());

        return $this->template->render("services/extra_flags/user_own_service", [
            'buttonEdit' => $buttonEdit,
            'authData' => $userService->getAuthData(),
            'userServiceId' => $userService->getId(),
            'expire' => convert_expire($userService->getExpire()),
            'moduleId' => $this->getModuleId(),
            'serverName' => $server->getName(),
            'serviceName' => $this->service->getName(),
            'type' => $this->getTypeName2($userService->getType()),
        ]);
    }

    public function userOwnServiceEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $validator = new Validator($body, [
            'password' => [
                new ExtraFlagPasswordRule(),
                new PasswordRule(),
                new ExtraFlagPasswordDiffersRule(),
            ],
        ]);
        $this->verifyUserServiceData($validator);

        $validated = $validator->validateOrFail();
        $result = $this->userServiceEdit($userService, $validated);

        if ($result) {
            $this->logger->logWithActor('log_user_edited_service', $userService->getId());
        }

        return $result;
    }

    // ----------------------------------------------------------------------------------
    // ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

    /**
     * @param ExtraFlagUserService $userService
     * @param array                $data
     * @return bool
     */
    private function userServiceEdit(ExtraFlagUserService $userService, array $data)
    {
        $forever = array_get($data, 'expire') === null;
        $expire = as_int(array_get($data, 'expire', $userService->getExpire()));
        $type = as_int(array_get($data, 'type', $userService->getType()));
        $authData = array_get($data, 'auth_data', $userService->getAuthData());
        $serverId = as_int(array_get($data, 'server', $userService->getServerId()));
        $uid = as_int(array_get($data, 'uid'));
        $shouldUidBeUpdated = array_key_exists('uid', $data);

        // Type is changed to SteamID from non-sid
        if (
            $type === ExtraFlagType::TYPE_SID &&
            $userService->getType() !== ExtraFlagType::TYPE_SID
        ) {
            $password = array_get($data, 'password', "");
            $shouldPasswordBeUpdated = true;
        } else {
            $password = array_get($data, 'password');
            $shouldPasswordBeUpdated = !!strlen($password);
        }

        $set = [];

        if ($shouldPasswordBeUpdated) {
            $set['password'] = $password;
        }

        if ($shouldUidBeUpdated) {
            $set['uid'] = $uid;
        }

        if ($forever) {
            $set['expire'] = -1;
        }

        // Sprawdzenie czy nie ma już takiej usługi
        $table = $this::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS usef ON us.id = usef.us_id " .
                "WHERE us.service = ? AND `server` = ? AND `type` = ? AND `auth_data` = ? AND `id` != ?"
        );
        $statement->execute([
            $this->service->getId(),
            $serverId,
            $type,
            $authData,
            $userService->getId(),
        ]);
        $existingUserService = $statement->fetch();

        if ($existingUserService) {
            // Since $shouldUidBeUpdated is false we can assume that it is action done via ACP
            // not by "user own service edit"
            $canManageThisUserService =
                !$shouldUidBeUpdated && $userService->getUid() != $existingUserService['uid'];

            if ($canManageThisUserService) {
                throw new ValidationException([
                    'auth_data' => [$this->lang->t('service_isnt_yours')],
                ]);
            }

            $this->userServiceRepository->delete($userService->getId());

            // Dodajemy expire
            if (!$forever) {
                $set['expire'] = new Expression("( `expire` - UNIX_TIMESTAMP() + $expire )");
            }

            // Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
            $affected = $this->userServiceRepository->updateWithModule(
                $this,
                $existingUserService['id'],
                $set
            );
        } else {
            $set['service'] = $this->service->getId();
            $set['server'] = $serverId;
            $set['type'] = $type;
            $set['auth_data'] = $authData;

            if (!$forever) {
                $set['expire'] = $expire;
            }

            $affected = $this->userServiceRepository->updateWithModule(
                $this,
                $userService->getId(),
                $set
            );
        }

        // Ustaw jednakowe hasła, żeby potem nie było problemów z różnymi hasłami
        if ($shouldPasswordBeUpdated) {
            $table = $this::USER_SERVICE_TABLE;
            $this->db
                ->statement(
                    "UPDATE `$table` " .
                        "SET `password` = ? " .
                        "WHERE `server` = ? AND `type` = ? AND `auth_data` = ?"
                )
                ->execute([$password, $serverId, $type, $authData]);
        }

        // Przelicz flagi tylko wtedy, gdy coś się zmieniło
        if (!$affected) {
            return false;
        }

        // Odśwież flagi gracza ( przed zmiana danych )
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // Odśwież flagi gracza ( już po edycji )
        $this->playerFlagService->recalculatePlayerFlags($serverId, $type, $authData);

        return true;
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

    // TODO Allow direct billing
    public function serviceTakeOver(array $body)
    {
        $user = $this->auth->user();
        $validator = new Validator(
            array_merge($body, [
                'auth_data' => trim(array_get($body, 'auth_data')),
                'password' => array_get($body, 'password') ?: "",
                'server_id' => as_int(array_get($body, 'server_id')),
                'type' => as_int(array_get($body, 'type')),
            ]),
            [
                'auth_data' => [new RequiredRule(), new ExtraFlagAuthDataRule()],
                'password' => [new ExtraFlagPasswordRule()],
                'payment_method' => [
                    new InArrayRule([Purchase::METHOD_SMS, Purchase::METHOD_TRANSFER]),
                ],
                'payment_id' => [new RequiredRule()],
                'server_id' => [new RequiredRule()],
                'type' => [new RequiredRule(), new ExtraFlagTypeRule()],
            ]
        );

        $validated = $validator->validateOrFail();
        $paymentMethod = $validated['payment_method'];
        $paymentId = $validated['payment_id'];
        $serverId = $validated['server_id'];
        $authData = $validated['auth_data'];
        $type = $validated['type'];
        $password = $validated['password'];

        if ($paymentMethod == Purchase::METHOD_TRANSFER) {
            $statement = $this->db->statement(
                "SELECT * FROM ({$this->transactionRepository->getQuery()}) as t " .
                    "WHERE t.payment = 'transfer' AND t.payment_id = ? AND `service` = ? AND `server` = ? AND `auth_data` = ?"
            );
            $statement->execute([$paymentId, $this->service->getId(), $serverId, $authData]);

            if (!$statement->rowCount()) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->t('no_user_service'),
                    'positive' => false,
                ];
            }
        } elseif ($paymentMethod == Purchase::METHOD_SMS) {
            $statement = $this->db->statement(
                "SELECT * FROM ({$this->transactionRepository->getQuery()}) as t " .
                    "WHERE t.payment = 'sms' AND t.sms_code = ? AND `service` = ? AND `server` = ? AND `auth_data` = ?"
            );
            $statement->execute([$paymentId, $this->service->getId(), $serverId, $authData]);

            if (!$statement->rowCount()) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->t('no_user_service'),
                    'positive' => false,
                ];
            }
        }

        // TODO: Usunac md5
        $statement = $this->db->statement(
            "SELECT `id` FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usef ON us.id = usef.us_id " .
                "WHERE us.service = ? AND `server` = ? AND `type` = ? AND `auth_data` = ? AND ( `password` = ? OR `password` = ? )"
        );
        $statement->execute([
            $this->service->getId(),
            $serverId,
            $type,
            $authData,
            $password,
            md5($password),
        ]);

        if (!$statement->rowCount()) {
            return [
                'status' => "no_service",
                'text' => $this->lang->t('no_user_service'),
                'positive' => false,
            ];
        }

        $row = $statement->fetch();
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
}
