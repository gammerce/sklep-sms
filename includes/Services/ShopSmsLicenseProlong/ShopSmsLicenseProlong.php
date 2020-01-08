<?php
namespace App\Services\ShopSmsLicenseProlong;

use App\Loggers\DatabaseLogger;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\System\Auth;
use App\System\Heart;
use App\LicenseServerService;
use App\Models\Purchase;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class ShopSmsLicenseProlong extends ShopSmsLicenseProlongSimple implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceUserServiceAdminAdd
{
    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    /** @var Auth */
    protected $auth;

    /** @var Heart */
    protected $heart;

    /** @var LicenseServerService */
    protected $licenseServerService;

    /** @var BoughtServiceService */
    protected $boughtServiceService;

    /** @var DatabaseLogger */
    protected $logger;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
    }

    // Formularz pokazywany podczas zakupu licencji
    public function purchaseFormGet(array $query)
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);

        return $this->template->render("services/shopsms_license_prolong/purchase_form", [
            'identifier' => $request->query->get("identifier", ""),
            'serviceId' => $this->service->getId(),
            'serviceTag' => $this->service->getTag(),
            'user' => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate($body)
    {
        $identifier = $body['identifier'];
        $warnings = [];

        // Ilość
        if ($warning = check_for_warnings("number", $body['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($body['amount'] < 30) {
            $warnings['amount'][] = $this->lang->t('value_must_be_ge_than', 30);
        }

        // Sprawdzamy czy podana licencja jest w bazie
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT 1 FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS m ON m.us_id = us.id " .
                    "WHERE m.identifier = '%s' AND us.expire != '-1'",
                [$identifier]
            )
        );

        if (!$this->db->numRows($result)) {
            $warnings['license_data'][] = $this->lang->t('wrong_license_data');
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

        $purchaseData = new Purchase($this->auth->user());
        $purchaseData->setOrder([
            'amount' => $body['amount'],
            'identifier' => $identifier,
        ]);
        $purchaseData->setPayment([
            'cost' => $this->getCost($body) * $body['amount'],
            'no_sms' => true,
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $identifier = $purchaseData->getOrder('identifier');

        return $this->template->render(
            "services/shopsms_license_prolong/order_details",
            compact('purchaseData', 'identifier') + [
                'serviceName' => $this->service->getName(),
                'serviceTag' => $this->service->getTag(),
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS m ON m.us_id = us.id " .
                    "WHERE m.identifier = '%s'",
                [$purchaseData->getOrder('identifier')]
            )
        );

        $userService = $this->db->fetchArrayAssoc($result);

        $lifetime = $purchaseData->getOrder('amount') * 24 * 60 * 60;
        $result = $this->licenseServerService->prolong(
            $userService['external_license_id'],
            $lifetime
        );
        $expiresAt = $result['expires_at'];

        // Aktualizujemy informacje o licencji w sklepie
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET `uid` = %s, `expire` = '%d'" .
                    "WHERE `id` = '%d'",
                [
                    $purchaseData->user->getUid() != 0 ? $purchaseData->user->getUid() : '`uid`',
                    $expiresAt,
                    $userService['us_id'],
                ]
            )
        );

        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            0,
            $purchaseData->getOrder('amount'),
            $userService['identifier'],
            $userService['email'],
            [
                'expire' => date($this->settings->getDateFormat(), $expiresAt),
            ]
        );
    }

    public function purchaseInfo($action, array $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $identifier = $data['auth_data'];

        if ($action == "email") {
            return $this->template->render(
                "services/shopsms_license_prolong/purchase_info_email",
                compact('identifier', 'data'),
                true,
                false
            );
        }

        if ($action == "web") {
            return $this->template->render(
                "services/shopsms_license_prolong/purchase_info_web",
                [
                    'serviceName' => $this->service->getName(),
                    'data' => $data,
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->t('license_prolonged', $identifier, $data['amount']),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function userServiceAdminAddFormGet()
    {
        return $this->template->render(
            "services/shopsms_license_prolong/user_service_admin_add",
            [
                'moduleId' => $this->getModuleId(),
            ],
            true,
            false
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
    public function userServiceAdminAdd($body)
    {
        $warnings = [];

        // License name
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS m ON m.us_id = us.id " .
                    "WHERE m.identifier = '%s' AND us.expire != '-1'",
                [$body['identifier']]
            )
        );

        $userService = [];
        if (!$this->db->numRows($result)) {
            $warnings['identifier'][] = $this->lang->t('wrong_license_data');
        } else {
            $userService = $this->db->fetchArrayAssoc($result);
        }

        // Amount
        if ($warning = check_for_warnings("number", $body['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($body['amount'] < 0) {
            $warnings['amount'][] = $this->lang->t('days_quantity_positive');
        }

        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $user = $this->auth->user();

        // Dodawanie informacji o płatności
        $paymentId = pay_by_admin($user);

        $purchaseData = new Purchase($this->heart->getUser($userService['uid']));
        $purchaseData->setService($this->service->getId());
        $purchaseData->setPayment([
            'method' => 'admin',
            'payment_id' => $paymentId,
        ]);
        $purchaseData->setOrder([
            'identifier' => $body['identifier'],
            'amount' => $body['amount'],
        ]);
        $boughtServiceId = $this->purchase($purchaseData);

        $this->logger->logWithActor('log_user_service_added', $boughtServiceId);

        return [
            'status' => 'ok',
            'text' => $this->lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function actionExecute($action, $body)
    {
        if ($action === "get_cost") {
            $cost = $this->getCost($body) * $body['amount'];
            return $cost !== null
                ? number_format($cost / 100, 2) . " " . $this->settings->getCurrency()
                : $this->lang->t('none');
        }

        throw new UnexpectedValueException();
    }

    /**
     * Zwraca dzienny
     *
     * @param array $body
     * @return int
     */
    private function getCost(array $body)
    {
        if (!my_is_integer($body['amount']) || $body['amount'] < 30) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT `cost_daily` FROM `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` " .
                "WHERE `identifier` = ?"
        );
        $statement->execute([$body['identifier']]);
        $costDaily = $statement->fetchColumn();

        if ($costDaily === null) {
            return null;
        }

        return ceil($costDaily * $this->getBargain($body['amount']));
    }

    private function getBargain($daysAmount)
    {
        if ($daysAmount >= 730) {
            return 0.6;
        }

        if ($daysAmount >= 365) {
            return 0.8;
        }

        return 1.0;
    }

    public function showOnWeb()
    {
        return true;
    }
}
