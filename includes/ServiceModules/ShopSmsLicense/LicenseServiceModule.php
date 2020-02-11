<?php
namespace App\ServiceModules\ShopSmsLicense;

use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\LicenseUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\BoughtServiceService;
use App\Payment\PurchaseSerializer;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\ServiceModules\ShopSmsLicense\Rules\LicenseEnginesRule;
use App\Services\LicenseServerService;
use App\Services\PriceTextService;
use App\Services\UserServiceService;
use App\Support\QueryParticle;
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

// https://stackoverflow.com/a/2040279
function generateUUID4()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

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
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";
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

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    /** @var LicenseUserServiceRepository */
    private $licenseUserServiceRepository;

    /** @var PriceTextService */
    private $priceTextService;

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
        $this->userServiceRepository = $this->app->make(UserServiceRepository::class);
        $this->purchaseSerializer = $this->app->make(PurchaseSerializer::class);
        $this->licenseUserServiceRepository = $this->app->make(LicenseUserServiceRepository::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
    }

    /**
     * @param array $data
     * @return LicenseUserService
     */
    public function mapToUserService(array $data)
    {
        return $this->licenseUserServiceRepository->mapToModel($data);
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

        $queryParticle = new QueryParticle();

        if (isset($query['search'])) {
            $queryParticle->extend(
                create_search_query(
                    [
                        "us.id",
                        "us.uid",
                        "u.username",
                        "s.name",
                        "m.external_license_id",
                        "m.identifier",
                        'm.cost_daily',
                    ],
                    $query['search']
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS us.id, us.uid, u.username, s.id AS `service_id`, " .
                "s.name AS `service`, us.expire, m.identifier, m.external_license_id, m.cost_daily " .
                "FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS m ON m.us_id = us.id " .
                "LEFT JOIN `ss_services` AS s ON s.id = m.service " .
                "LEFT JOIN `ss_users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );

        $table->setDbRowsCount($this->db->query("SELECT FOUND_ROWS()")->fetchColumn());

        foreach ($statement as $row) {
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
            $bodyRow->addCell(new Cell($this->priceTextService->getPriceText($row['cost_daily'])));
            $bodyRow->addCell(new Cell(convert_expire($row['expire'])));

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
        $validator = new Validator(
            array_merge($body, [
                'email' => trim(array_get($body, 'email')),
            ]),
            [
                'amount' => [new RequiredRule(), new IntegerRule(), new MinValueRule(30)],
                'email' => [new RequiredRule(), new EmailRule()],
                'engines' => [new LicenseEnginesRule()],
                'platform_amxmodx' => [],
                'platform_sourcemod' => [],
            ]
        );

        $validated = $validator->validateOrFail();

        $costDaily = $this->getCostDaily($validated);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $validated['amount'],
            'engines' => [
                'amxx' => $validated['platform_amxmodx'],
                'sm' => $validated['platform_sourcemod'],
            ],
            'cost_daily' => $costDaily,
        ]);
        $purchase->setEmail($validated['email']);
        $purchase->setPayment([
            Purchase::PAYMENT_TRANSFER_PRICE => $this->getCost(
                $costDaily,
                $validated['amount'],
                true
            ),
            Purchase::PAYMENT_SMS_DISABLED => true,
        ]);
    }

    public function orderDetails(Purchase $purchase)
    {
        return $this->template->renderNoComments("services/shopsms_license/order_details", [
            'costMonthly' => $this->priceTextService->getPriceText(
                $purchase->getOrder('cost_daily') * 30
            ),
            'email' => $purchase->getEmail() ?: $this->lang->t('none'),
            'engines' => $this->formatOrderEngines($purchase->getOrder('engines')),
            'quantity' => $purchase->getOrder(Purchase::ORDER_QUANTITY),
            'serviceName' => $this->service->getName(),
            'serviceTag' => $this->service->getTag(),
        ]);
    }

    public function purchase(Purchase $purchase)
    {
        $engines = $purchase->getOrder('engines');
        $lifetime = $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60;

        $result = $this->licenseServerService->create(
            $lifetime,
            !!$engines['amxx'],
            !!$engines['sm']
        );
        $externalLicenseId = $result['id'];
        $token = $result['token'];
        $expiresAt = $result['expires_at'];
        $identifier = generateUUID4();

        // Dodajemy usługę użytkownika do bazy sklepu
        $userServiceId = $this->userServiceRepository->createFixedExpire(
            $this->service->getId(),
            $expiresAt,
            $purchase->user->getUid()
        );

        $table = $this::USER_SERVICE_TABLE;
        $this->db
            ->statement(
                "INSERT INTO `$table` SET " .
                    "`us_id` = ?, " .
                    "`service` = ?, " .
                    "`identifier` = ?, " .
                    "`external_license_id` = ?, " .
                    "`cost_daily` = ?, " .
                    "`email` = ?, " .
                    "`platform_amxmodx` = ?, " .
                    "`platform_sourcemod` = ?"
            )
            ->execute([
                $userServiceId,
                $this->service->getId(),
                $identifier,
                $externalLicenseId,
                $purchase->getOrder('cost_daily'),
                $purchase->getEmail(),
                $engines['amxx'],
                $engines['sm'],
            ]);

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $identifier,
            $purchase->getEmail(),
            [
                'token' => $token,
                'identifier' => $identifier,
                'expire' => date($this->settings->getDateFormat(), $expiresAt),
                'engines' => $this->formatOrderEngines($engines),
            ]
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $engines = $transaction->getExtraDatum("engines");

        if ($action === "email") {
            return $this->template->renderNoComments(
                "services/shopsms_license/purchase_info_email",
                [
                    'engines' => $engines,
                    'expire' => $transaction->getExtraDatum("expire"),
                    'identifier' => $transaction->getExtraDatum("identifier"),
                    'token' => $transaction->getExtraDatum("token"),
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments("services/shopsms_license/purchase_info_web", [
                'email' => $transaction->getEmail(),
                'engines' => $engines,
                'expire' => $transaction->getExtraDatum("expire"),
                'identifier' => $transaction->getExtraDatum("identifier"),
                'token' => $transaction->getExtraDatum("token"),
                'serviceName' => $this->service->getName(),
            ]);
        }

        if ($action === "payment_log") {
            return [
                'text' => $this->lang->t('license_bought', $transaction->getQuantity()),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function userServiceAdminAddFormGet()
    {
        return $this->template->renderNoComments(
            "services/shopsms_license/user_service_admin_add",
            [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

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

        $engines = [];
        if ($userService->hasPlatformAmxModX()) {
            $engines[] = "AMX Mod X";
        }
        if ($userService->hasPlatformSourceMod()) {
            $engines[] = "SOURCEMOD";
        }

        return $this->template->render("services/shopsms_license/user_own_service", [
            'buttonEdit' => $buttonEdit,
            'costMonthly' => $this->priceTextService->getPriceText(
                $userService->getCostDaily() * 30
            ),
            'email' => $userService->getEmail() ?: $this->lang->t('none'),
            'engines' => $this->formatEngines($engines),
            'expire' => convert_expire($userService->getExpire()),
            'identifier' => $userService->getIdentifier(),
            'moduleId' => $this->getModuleId(),
            'serviceName' => $this->service->getName(),
            'userServiceId' => $userService->getId(),
        ]);
    }

    public function userOwnServiceEditFormGet(UserService $userService)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

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

        return $this->template->render("services/shopsms_license/user_own_service_edit", [
            'costMonthly' => $this->priceTextService->getPriceText(
                $userService->getCostDaily() * 30
            ),
            'email' => $userService->getEmail(),
            'engines' => $engines,
            'expire' => convert_expire($userService->getExpire()),
            'identifier' => $userService->getIdentifier(),
            'serviceId' => $this->service->getId(),
            'serviceName' => $this->service->getName(),
        ]);
    }

    public function userOwnServiceEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $validator = new Validator(
            array_merge($body, [
                'email' => array_get($body, 'email'),
            ]),
            [
                'id' => [],
                'email' => [new RequiredRule(), new EmailRule()],
                'engines' => [new LicenseEnginesRule()],
                'password' => [new PasswordRule()],
                'platform_amxmodx' => [],
                'platform_sourcemod' => [],
            ]
        );

        $validated = $validator->validateOrFail();

        $costData = $this->getCostUserEdit($validated, $userService);

        $purchase = new Purchase($this->auth->user());
        $purchase->setService('ss_license_edit');
        $purchase->setOrder([
            'user_service_id' => $validated['id'],
            'cost_daily' => $costData['cost_daily'],
            'bargain' => $costData['bargain'],
            'password' => $validated['password'],
            'engines' => [
                'amxx' => $validated['platform_amxmodx'],
                'sm' => $validated['platform_sourcemod'],
            ],
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_TRANSFER_PRICE => $costData['surcharge'] * $costData['bargain'],
            Purchase::PAYMENT_SMS_DISABLED => true,
        ]);
        $purchase->setEmail($validated['email']);

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
        $validator = new Validator($body, [
            'service_id' => [new RequiredRule()],
            'token' => [new RequiredRule()],
        ]);

        $validated = $validator->validateOrFail();

        try {
            $response = $this->licenseServerService->getByToken($validated['token']);
        } catch (Exception $e) {
            return [
                'status' => "no_service",
                'text' => $this->lang->t('no_user_service'),
                'positive' => false,
            ];
        }

        $table = $this::USER_SERVICE_TABLE;
        $statement = $this->db->statement(
            "SELECT `us_id` FROM `$table` WHERE `service` = ? AND `external_license_id` = ?"
        );
        $statement->execute([$validated['service_id'], $response['id']]);

        $row = $statement->fetch();
        $userServiceId = $row['us_id'];

        $user = $this->auth->user();
        $statement = $this->db->statement("UPDATE `ss_user_service` SET `uid` = ? WHERE `id` = ?");
        $statement->execute([$user->getUid(), $userServiceId]);

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

            return $this->priceTextService->getPriceText(
                $this->getCost($this->getCostDaily($body), $body['amount'], true)
            );
        }

        if ($action === "get_cost_user_edit") {
            $userService = $this->userServiceService->findOne($body['user_service_id']);

            if (!($userService instanceof LicenseUserService)) {
                throw new UnexpectedValueException();
            }

            $costData = $this->getCostUserEdit($body, $userService);
            $costData['surcharge'] = $this->priceTextService->getPriceText(
                $costData['surcharge'] * $costData['bargain']
            );
            $costData['cost_monthly'] = $this->priceTextService->getPriceText(
                $costData['cost_monthly'] * $costData['bargain']
            );
            $costData['cost_daily'] = $this->priceTextService->getPriceText(
                $costData['cost_daily'] * $costData['bargain']
            );

            return json_encode($costData);
        }

        if ($action === "regenerate_token") {
            $identifier = array_get($body, 'identifier');

            $table = $this::USER_SERVICE_TABLE;
            $statement = $this->db->statement(
                "SELECT `external_license_id` FROM `$table` WHERE `identifier` = ?"
            );
            $statement->execute([$identifier]);

            if (!$statement->rowCount()) {
                return 'Invalid identifier';
            }

            $row = $statement->fetch();
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
     * @param array $body
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
        if ($daysCount >= 365) {
            return 0.8;
        }

        return 1.0;
    }

    public function showOnWeb()
    {
        return true;
    }

    private function formatOrderEngines(array $engines)
    {
        $output = [];

        if (array_get($engines, 'amxx')) {
            $output[] = "AMX Mod X";
        }

        if (array_get($engines, 'sm')) {
            $output[] = "SOURCEMOD";
        }

        return $this->formatEngines($output);
    }

    private function formatEngines(array $engines)
    {
        if ($engines) {
            return implode(", ", $engines);
        }

        return $this->lang->t('none');
    }
}
