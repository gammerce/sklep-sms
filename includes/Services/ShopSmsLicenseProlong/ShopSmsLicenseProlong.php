<?php
namespace App\Services\ShopSmsLicenseProlong;

use App\Auth;
use App\Heart;
use App\LicenseServerService;
use App\Models\Purchase;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\Settings;
use App\TranslationManager;
use App\Translator;
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

    /** @var Translator */
    protected $langShop;

    /** @var Settings */
    protected $settings;

    /** @var Auth */
    protected $auth;

    /** @var Heart */
    protected $heart;

    /** @var LicenseServerService */
    protected $licenseServerService;

    public function __construct($service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->settings = $this->app->make(Settings::class);
        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
    }

    // Formularz pokazywany podczas zakupu licencji
    public function purchase_form_get()
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);

        return $this->template->render(
            "services/shopsms_license_prolong/purchase_form",
            compact('user') + [
                'identifier' => htmlspecialchars($request->query->get("identifier", "")),
                'serviceId' => $this->service['id'],
                'serviceTag' => $this->service['tag'],
                'user' => $this->auth->user(),
            ]
        );
    }

    public function purchase_form_validate($post)
    {
        $identifier = $post['identifier'];
        $warnings = [];

        // Ilość
        if ($warning = check_for_warnings("number", $post['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($post['amount'] < 30) {
            $warnings['amount'][] = $this->lang->sprintf(
                $this->lang->translate('value_must_be_ge_than'),
                30
            );
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

        if (!$this->db->num_rows($result)) {
            $warnings['license_data'][] = $this->lang->translate('wrong_license_data');
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchase_data = new Purchase();
        $purchase_data->setOrder([
            'amount' => $post['amount'],
            'identifier' => $identifier,
        ]);
        $purchase_data->setPayment([
            'cost' => $this->getCost($post) * $post['amount'],
            'no_sms' => true,
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchase_data,
        ];
    }

    public function order_details($purchase_data)
    {
        $identifier = htmlspecialchars($purchase_data->getOrder('identifier'));

        return $this->template->render(
            "services/shopsms_license_prolong/order_details",
            compact('purchase_data', 'identifier') + [
                'serviceName' => $this->service['name'],
                'serviceTag' => $this->service['tag'],
            ],
            true,
            false
        );
    }

    public function purchase($purchase_data)
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
                [$purchase_data->getOrder('identifier')]
            )
        );

        $user_service = $this->db->fetch_array_assoc($result);

        $lifetime = $purchase_data->getOrder('amount') * 24 * 60 * 60;
        $result = $this->licenseServerService->prolong(
            $user_service['external_license_id'],
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
                    $purchase_data->user->getUid() != 0 ? $purchase_data->user->getUid() : '`uid`',
                    $expiresAt,
                    $user_service['us_id'],
                ]
            )
        );

        return add_bought_service_info(
            $purchase_data->user->getUid(),
            $purchase_data->user->getUsername(),
            $purchase_data->user->getLastIp(),
            $purchase_data->getPayment('method'),
            $purchase_data->getPayment('payment_id'),
            $this->service['id'],
            0,
            $purchase_data->getOrder('amount'),
            $user_service['identifier'],
            $user_service['email'],
            [
                'expire' => date($this->settings['date_format'], $expiresAt),
            ]
        );
    }

    public function purchase_info($action, $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $identifier = htmlspecialchars($data['auth_data']);

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
                    'serviceName' => $this->service['name'],
                    'data' => $data,
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->sprintf(
                    $this->lang->translate('license_prolonged'),
                    $identifier,
                    $data['amount']
                ),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function user_service_admin_add_form_get()
    {
        return $this->template->render(
            "services/shopsms_license_prolong/user_service_admin_add",
            [
                'moduleId' => $this->get_module_id(),
            ],
            true,
            false
        );
    }

    /**
     * Metoda sprawdza dane formularza podczas dodawania użytkownikowi usługi w PA
     * i gdy wszystko jest okej, to ją dodaje.
     *
     * @param array $post Dane $_POST
     * @return array
     *  status => id wiadomości
     *  text => treść wiadomości
     *  positive => czy udało się dodać usługę
     */
    public function user_service_admin_add($post)
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
                [$post['identifier']]
            )
        );

        $user_service = [];
        if (!$this->db->num_rows($result)) {
            $warnings['identifier'][] = $this->lang->translate('wrong_license_data');
        } else {
            $user_service = $this->db->fetch_array_assoc($result);
        }

        // Amount
        if ($warning = check_for_warnings("number", $post['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($post['amount'] < 0) {
            $warnings['amount'][] = $this->lang->translate('days_quantity_positive');
        }

        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $user = $this->auth->user();

        // Dodawanie informacji o płatności
        $payment_id = pay_by_admin($user);

        $purchase_data = new Purchase();
        $purchase_data->setService($this->service['id']);
        $purchase_data->user = $this->heart->get_user($user_service['uid']);
        $purchase_data->setPayment([
            'method' => 'admin',
            'payment_id' => $payment_id,
        ]);
        $purchase_data->setOrder([
            'identifier' => $post['identifier'],
            'amount' => $post['amount'],
        ]);
        $bought_service_id = $this->purchase($purchase_data);

        log_info(
            $this->langShop->sprintf(
                $this->langShop->translate('admin_added_user_service'),
                $user->getUsername(),
                $user->getUid(),
                $bought_service_id
            )
        );

        return [
            'status' => 'ok',
            'text' => $this->lang->translate('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function action_execute($action, $post)
    {
        if ($action === "get_cost") {
            $cost = $this->getCost($post) * $post['amount'];
            return $cost !== null
                ? number_format($cost / 100, 2) . " " . $this->settings['currency']
                : $this->lang->translate('none');
        }

        throw new UnexpectedValueException();
    }

    /**
     * Zwraca dzienny
     *
     * @param array $post Dane $_POST formularza zakupu
     * @return int
     */
    private function getCost($post)
    {
        if (!my_is_integer($post['amount']) || $post['amount'] < 30) {
            return null;
        }

        $cost_daily = $this->db->get_column(
            $this->db->prepare(
                "SELECT `cost_daily` FROM `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` " .
                "WHERE `identifier` = '%s'",
                [$post['identifier']]
            ),
            "cost_daily"
        );

        if ($cost_daily === null) {
            return null;
        }

        return ceil($cost_daily * $this->getBargain($post['amount']));
    }

    private function getBargain($days_amount)
    {
        if ($days_amount >= 730) {
            return 0.6;
        }

        if ($days_amount >= 365) {
            return 0.8;
        }

        return 1.0;
    }

    public function show_on_web()
    {
        return true;
    }
}
