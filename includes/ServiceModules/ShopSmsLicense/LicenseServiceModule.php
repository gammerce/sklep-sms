<?php
namespace App\ServiceModules\ShopSmsLicense;

use App\Models\LicenseUserService;
use App\Models\UserService;
use App\Payment\PurchaseSerializer;
use App\Services\LicenseServerService;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Services\UserServiceService;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use Exception;
use UnexpectedValueException;

class LicenseServiceModule extends ServiceModule implements
    IServiceUserServiceAdminDisplay,
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceTakeOver,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceUserServiceAdminAdd
{
    const MODULE_ID = "shopsms_license";
    const USER_SERVICE_TABLE = "user_service_shopsms_license";
    // Kwoty za dzień w groszach
    const COST_SHOP_PER_DAY = 40;
    const COST_ENGINE_PER_DAY = 20;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var Auth */
    private $auth;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var CurrentPage */
    private $currentPage;

    /** @var LicenseServerService */
    private $licenseServerService;

    /** @var UserServiceService */
    private $userServiceService;

    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    public function __construct(Service $service = null)
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
        $this->userServiceService = $this->app->make(UserServiceService::class);
        $this->purchaseSerializer = $this->app->make(PurchaseSerializer::class);
    }

    /**
     * @param array $data
     * @return LicenseUserService
     */
    public function mapToUserService(array $data)
    {
        return new LicenseUserService(
            as_int($data['id']),
            $data['service'],
            as_int($data['uid']),
            as_int($data['expire']),
            $data['identifier'],
            $data['external_license_id'],
            $data['email'],
            as_int($data['cost_daily']),
            (bool) $data['platform_amxmodx'],
            (bool) $data['platform_sourcemod']
        );
    }

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->t('licenses');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('identifier')));
        $table->addHeadCell(new HeadCell($this->lang->t('external_license_id')));
        $table->addHeadCell(new HeadCell($this->lang->t('cost_daily')));
        $table->addHeadCell(new HeadCell($this->lang->t('expires')));

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

        $table->setDbRowsCount($this->db->query("SELECT FOUND_ROWS()")->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid'] ? $row['username'] . " ({$row['uid']})" : $this->lang->t('none')
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
                        ? $this->lang->t('never')
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

    public function purchaseFormGet(array $query)
    {
        return $this->template->render("services/shopsms_license/purchase_form", [
            'user' => $this->auth->user(),
            'serviceId' => $this->service->getId(),
            'serviceTag' => $this->service->getTag(),
            'days' => array_get($query, "days"),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $warnings = [];

        // Wybranie przynajmniej jednego silnika gry
        if ($body['platform_amxmodx'] == "0" && $body['platform_sourcemod'] == "0") {
            $warnings['engines'][] = $this->lang->t('no_engine_choosen');
        }

        // Ilość
        if ($warning = check_for_warnings("number", $body['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($body['amount'] < 30) {
            $warnings['amount'][] = $this->lang->t('value_must_be_ge_than', 30);
        }

        // E-mail
        $body['email'] = trim($body['email']);
        if ($warning = check_for_warnings("email", $body['email'])) {
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

        $costDaily = $this->getCostDaily($body);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $body['amount'],
            'engines' => [
                'amxx' => $body['platform_amxmodx'],
                'sm' => $body['platform_sourcemod'],
            ],
            'cost_daily' => $costDaily,
        ]);
        $purchase->setEmail($body['email']);
        $purchase->setPayment([
            Purchase::PAYMENT_TRANSFER_PRICE => $this->getCost($costDaily, $body['amount'], true),
            Purchase::PAYMENT_SMS_DISABLED => true,
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
        ];
    }

    public function orderDetails(Purchase $purchase)
    {
        $engines = [];
        $tmpEngines = $purchase->getOrder('engines');
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        if (empty($engines)) {
            $engines = $this->lang->t('none');
        } else {
            $engines = implode(", ", $engines);
        }

        $email = $purchase->getEmail() ?: $this->lang->t('none');
        $costMonthly =
            number_format(($purchase->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings->getCurrency();

        return $this->template->renderNoComments(
            "services/shopsms_license/order_details",
            compact('engines', 'costMonthly', 'email') + [
                'quantity' => $purchase->getOrder('amount'),
                'serviceName' => $this->service->getName(),
                'serviceTag' => $this->service->getTag(),
            ]
        );
    }

    public function purchase(Purchase $purchase)
    {
        $tmpEngines = $purchase->getOrder('engines');
        $lifetime = $purchase->getOrder('amount') * 24 * 60 * 60;

        $result = $this->licenseServerService->create(
            $lifetime,
            !!$tmpEngines['amxx'],
            !!$tmpEngines['sm']
        );
        $externalLicenseId = $result['id'];
        $token = $result['token'];
        $expiresAt = $result['expires_at'];
        $identifier = generateUUID4();

        // Dodajemy usługę użytkownika do bazy sklepu
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET `uid` = '%d', `service` = '%s', `expire` = '%d'",
                [$purchase->user->getUid(), $this->service->getId(), $expiresAt]
            )
        );
        $userServiceId = $this->db->lastId();

        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "SET `us_id` = '%d', " .
                    "`service` = '%s', " .
                    "`identifier` = '%s', " .
                    "`external_license_id` = '%d', " .
                    "`cost_daily` = '%s', " .
                    "`email` = '%s', " .
                    "`platform_amxmodx` = '%d', " .
                    "`platform_sourcemod` = '%d'",
                [
                    $userServiceId,
                    $this->service->getId(),
                    $identifier,
                    $externalLicenseId,
                    $purchase->getOrder('cost_daily'),
                    $purchase->getEmail(),
                    $tmpEngines['amxx'],
                    $tmpEngines['sm'],
                ]
            )
        );

        // Dodanie informacji o zakupie usługi
        $engines = [];
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        if (!empty($engines)) {
            $engines = implode(", ", $engines);
        } else {
            $engines = $this->lang->t('none');
        }

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder('amount'),
            $identifier,
            $purchase->getEmail(),
            [
                'token' => $token,
                'identifier' => $identifier,
                'expire' => date($this->settings->getDateFormat(), $expiresAt),
                'engines' => $engines,
            ]
        );
    }

    public function purchaseInfo($action, array $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $engines = $data['extra_data']['engines'];

        if ($action == "email") {
            return $this->template->render(
                "services/shopsms_license/purchase_info_email",
                compact('data', 'engines'),
                true,
                false
            );
        }

        if ($action == "web") {
            $email = $data['email'];
            return $this->template->render(
                "services/shopsms_license/purchase_info_web",
                compact('data', 'engines', 'email') + ['serviceName' => $this->service->getName()],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->t('license_bought', $data['amount']),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function userServiceAdminAddFormGet()
    {
        return $this->template->renderNoComments(
            "services/shopsms_license/user_service_admin_add",
            [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    /**
     * Metoda sprawdza dane formularza podczas dodawania użytkownikowi usługi w PA
     * i gdy wszystko jest okej, to ją dodaje.
     *
     * @param array $body Dane $_POST
     * @return array
     *  status => id wiadomości
     *  text => treść wiadomości
     *  positive => czy udało się dodać usługę
     */
    public function userServiceAdminAdd(array $body)
    {
        return [
            'status' => 'ok',
            'text' => $this->lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }

    // ------------------- My Current Services --------------------

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $identifier = $userService->getIdentifier();
        $expire = $userService->isForever()
            ? $this->lang->t('never')
            : convertDate($userService->getExpire());
        $email = $userService->getEmail() ?: $this->lang->t('none');
        $costMonthly = number_format(($userService->getCostDaily() * 30) / 100, 2);

        // Dostępne silniki
        $engines = [];
        if ($userService->hasPlatformAmxModX()) {
            $engines[] = "AMX Mod X";
        }
        if ($userService->hasPlatformSourceMod()) {
            $engines[] = "SOURCEMOD";
        }

        if (!empty($engines)) {
            $engines = implode(", ", $engines);
        } else {
            $engines = $this->lang->t('none');
        }

        return $this->template->render(
            "services/shopsms_license/user_own_service",
            compact('identifier', 'engines', 'email', 'expire', 'costMonthly', 'buttonEdit') + [
                'moduleId' => $this->getModuleId(),
                'serviceName' => $this->service->getName(),
                'userServiceId' => $userService->getId(),
            ]
        );
    }

    public function userOwnServiceEditFormGet(UserService $userService)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $identifier = $userService->getIdentifier();
        $expire = $userService->isForever()
            ? $this->lang->t('never')
            : convertDate($userService->getExpire());
        $email = $userService->getEmail();
        $costMonthly = number_format(($userService->getCostDaily() * 30) / 100, 2);

        $engines = [
            'amxx' => [
                'input' => $userService->hasPlatformAmxModX() ? "1" : "0",
                'div' => $userService->hasPlatformAmxModX() ? "active" : "",
            ],
            'sm' => [
                'input' => $userService->hasPlatformSourceMod() ? "1" : "0",
                'div' => $userService->hasPlatformSourceMod() ? "active" : "",
            ],
        ];

        return $this->template->render(
            "services/shopsms_license/user_own_service_edit",
            compact('identifier', 'expire', 'email', 'engines', 'costMonthly') + [
                'serviceId' => $this->service->getId(),
                'serviceName' => $this->service->getName(),
            ]
        );
    }

    public function userOwnServiceEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $warnings = [];

        // Wybranie przynajmniej jednego silnika gry
        if ($body['platform_amxmodx'] == "0" && $body['platform_sourcemod'] == '0') {
            $warnings['engines'][] = $this->lang->t('no_engine_choosen');
        }

        // E-mail
        $body['email'] = trim($body['email']);
        if ($warning = check_for_warnings("email", $body['email'])) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Hasło
        if (
            strlen($body['password']) &&
            ($warning = check_for_warnings("password", $body['password']))
        ) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }

        $costData = $this->getCostUserEdit($body, $userService);

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchase = new Purchase($this->auth->user());
        $purchase->setService('ss_license_edit');
        $purchase->setOrder([
            'user_service_id' => $body['id'],
            'cost_daily' => $costData['cost_daily'],
            'bargain' => $costData['bargain'],
            'password' => $body['password'],
            'engines' => [
                'amxx' => $body['platform_amxmodx'],
                'sm' => $body['platform_sourcemod'],
            ],
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_TRANSFER_PRICE => $costData['surcharge'] * $costData['bargain'],
            Purchase::PAYMENT_SMS_DISABLED => true,
        ]);
        $purchase->setEmail($body['email']);

        $purchaseData = $this->purchaseSerializer->serializeAndEncode($purchase);

        return [
            'status' => "payment",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'data' => [
                'data' => $purchaseData,
                'sign' => md5($purchaseData . $this->settings['random_key']),
            ],
        ];
    }

    public function serviceTakeOverFormGet()
    {
        return $this->template->render("services/shopsms_license/service_take_over");
    }

    public function serviceTakeOver(array $body)
    {
        // ID
        if (!strlen($body['token'])) {
            $warnings['token'][] = $this->lang->t('field_empty');
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

        try {
            $response = $this->licenseServerService->getByToken($body['token']);
        } catch (Exception $e) {
            return [
                'status' => "no_service",
                'text' => $this->lang->t('no_user_service'),
                'positive' => false,
            ];
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `us_id` FROM `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `service` = '%s' AND `external_license_id` = '%s'",
                [$body['service_id'], $response['id']]
            )
        );

        $row = $result->fetch();
        $userServiceId = $row['us_id'];

        $user = $this->auth->user();
        $statement = $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET `uid` = '%d' " .
                    "WHERE `id` = '%d'",
                [$user->getUid(), $userServiceId]
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

    public function userServiceDelete(UserService $userService, $who)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        // Nie usuwaj, jezeli jest mniej niz 30 dni po wygasnieciu
        if ($who != 'admin' && $userService->getExpire() + 60 * 60 * 24 * 30 > time()) {
            return false;
        }

        $this->licenseServerService->delete($userService->getExternalLicenseId());

        return true;
    }

    public function actionExecute($action, array $body)
    {
        if ($action === "get_cost") {
            if ($body['amount'] < 30) {
                return $this->lang->t('none');
            }

            return number_format(
                $this->getCost($this->getCostDaily($body), $body['amount'], true) / 100,
                2
            ) .
                ' ' .
                $this->settings->getCurrency();
        }

        if ($action === "get_cost_user_edit") {
            $userService = $this->userServiceService->findOne($body['user_service_id']);

            if (!($userService instanceof LicenseUserService)) {
                throw new UnexpectedValueException();
            }

            $costData = $this->getCostUserEdit($body, $userService);

            $costData['surcharge'] =
                number_format(($costData['surcharge'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings->getCurrency();
            $costData['cost_monthly'] =
                number_format(($costData['cost_monthly'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings->getCurrency();
            $costData['cost_daily'] =
                number_format(($costData['cost_daily'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings->getCurrency();

            return json_encode($costData);
        }

        if ($action === "regenerate_token") {
            $identifier = $body['identifier'];

            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT `external_license_id` FROM `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "WHERE `identifier` = '%s'",
                    [$identifier]
                )
            );

            if (!$result->rowCount()) {
                return 'Invalid identifier';
            }

            $row = $result->fetch();
            $externalLicenseId = $row['external_license_id'];

            try {
                $response = $this->licenseServerService->regenerateToken($externalLicenseId);
            } catch (Exception $e) {
                return $e->getMessage();
            }

            return json_encode([
                'token' => $response['token'],
            ]);
        }

        return 'no_action';
    }

    private function getCost($costDaily, $daysAmount, $bargain = true)
    {
        return ceil($costDaily * $daysAmount * ($bargain ? $this->getBargain($daysAmount) : 1));
    }

    /**
     * Zwraca koszt zakupu licencji
     *
     * @param array $body Dane $_POST formularza zakupu
     * @return int|null
     */
    private function getCostDaily(array $body)
    {
        $cost = $this::COST_SHOP_PER_DAY;
        $costEngines = 0;
        if ($body['platform_amxmodx']) {
            $costEngines += $this::COST_ENGINE_PER_DAY;
        }
        if ($body['platform_sourcemod']) {
            $costEngines += $this::COST_ENGINE_PER_DAY;
        }

        // Dodajemy koszt za kolejne silniki
        $cost += max(0, $costEngines - $this::COST_ENGINE_PER_DAY); // -5, bo pierwsza gra jest darmowa

        return ceil($cost);
    }

    /**
     * @param array $body
     * @param LicenseUserService $userService
     * @return array
     */
    private function getCostUserEdit(array $body, LicenseUserService $userService)
    {
        if (!$userService) {
            return null;
        }

        $daysLeft = ($userService->getExpire() - time()) / (24 * 60 * 60);

        $engines = [
            'amxx' => [
                'old' => $userService->hasPlatformAmxModX(),
                'new' => $body['platform_amxmodx'],
            ],
            'sm' => [
                'old' => $userService->hasPlatformSourceMod(),
                'new' => $body['platform_sourcemod'],
            ],
        ];

        $additionalCost = 0;
        foreach ($engines as $engine => $engineData) {
            // Jezeli anulujemy wsparcie dla jakiegos silnika, to tracimy wszelkie znizki
            // i przeliczamy normalnie koszt jaki wychodzi
            if ($engineData['old'] && !$engineData['new']) {
                $body['amount'] = $daysLeft; // Tworzymy tak jakby zapytanie z formularza zakupu
                $costDaily = $this->getCostDaily($body);
                break;
            }

            // Jezeli dodajemy wsparcie dla nowego silnika, to dodajemy do dniowki
            if ($engineData['new'] && !$engineData['old']) {
                $additionalCost += $this::COST_ENGINE_PER_DAY;
            }
        }

        if (!isset($costDaily)) {
            $costDaily = $userService->getCostDaily() + $additionalCost;
        }

        return [
            'surcharge' => max(0, ($costDaily - $userService->getCostDaily()) * $daysLeft),
            'cost_daily' => $costDaily,
            'cost_monthly' => $costDaily * 30,
            'bargain' => $this->getBargain($daysLeft),
        ];
    }

    private function getBargain($daysCount)
    {
        if ($daysCount >= 730) {
            return 0.6;
        }

        if ($daysCount >= 365) {
            return 0.8;
        }

        return 1.0;
    }

    public function showOnWeb()
    {
        return true;
    }
}
