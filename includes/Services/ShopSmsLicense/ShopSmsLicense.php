<?php
namespace App\Services\ShopSmsLicense;

use App\Models\Purchase;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceTakeOver;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use Exception;
use UnexpectedValueException;

class ShopSmsLicense extends ShopSmsLicenseSimple implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceTakeOver,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceUserServiceAdminAdd
{
    public function purchaseFormGet()
    {
        return $this->template->render("services/shopsms_license/purchase_form", [
            'user' => $this->auth->user(),
            'serviceId' => $this->service['id'],
            'serviceTag' => $this->service['tag'],
        ]);
    }

    public function purchaseFormValidate($post)
    {
        // Wybranie przynajmniej jednego silnika gry
        if ($post['platform_amxmodx'] == "0" && $post['platform_sourcemod'] == "0") {
            $warnings['engines'][] = $this->lang->translate('no_engine_choosen');
        }

        // Ilość
        if ($warning = check_for_warnings("number", $post['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($post['amount'] < 30) {
            $warnings['amount'][] = $this->lang->sprintf(
                $this->lang->translate('value_must_be_ge_than'),
                30
            );
        }

        // E-mail
        $post['email'] = trim($post['email']);
        if ($warning = check_for_warnings("email", $post['email'])) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
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

        $cost_daily = $this->getCostDaily($post);
        $purchase_data = new Purchase();
        $purchase_data->setOrder([
            'amount' => $post['amount'],
            'engines' => [
                'amxx' => $post['platform_amxmodx'],
                'sm' => $post['platform_sourcemod'],
            ],
            'cost_daily' => $cost_daily,
        ]);
        $purchase_data->setEmail($post['email']);
        $purchase_data->setPayment([
            'cost' => $this->getCost($cost_daily, $post['amount'], true),
            'no_sms' => true,
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchase_data,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $engines = [];
        $tmp_engines = $purchaseData->getOrder('engines');
        if ($tmp_engines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmp_engines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        if (empty($engines)) {
            $engines = $this->lang->translate('none');
        } else {
            $engines = implode(", ", $engines);
        }

        $email = strlen($purchaseData->getEmail())
            ? htmlspecialchars($purchaseData->getEmail())
            : $this->lang->translate('none');
        $cost_monthly =
            number_format(($purchaseData->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings['currency'];

        return $this->template->render(
            "services/shopsms_license/order_details",
            compact('purchaseData', 'engines', 'cost_monthly', 'email') + [
                'serviceName' => $this->service['name'],
                'serviceTag' => $this->service['tag'],
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        $tmp_engines = $purchaseData->getOrder('engines');
        $lifetime = $purchaseData->getOrder('amount') * 24 * 60 * 60;

        $result = $this->licenseServerService->create(
            $lifetime,
            !!$tmp_engines['amxx'],
            !!$tmp_engines['sm']
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
                [$purchaseData->user->getUid(), $this->service['id'], $expiresAt]
            )
        );
        $userServiceId = $this->db->last_id();

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
                    $this->service['id'],
                    $identifier,
                    $externalLicenseId,
                    $purchaseData->getOrder('cost_daily'),
                    $purchaseData->getEmail(),
                    $tmp_engines['amxx'],
                    $tmp_engines['sm'],
                ]
            )
        );

        // Dodanie informacji o zakupie usługi
        $engines = [];
        if ($tmp_engines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmp_engines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        if (!empty($engines)) {
            $engines = implode(", ", $engines);
        } else {
            $engines = $this->lang->translate('none');
        }

        return add_bought_service_info(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service['id'],
            0,
            $purchaseData->getOrder('amount'),
            $identifier,
            $purchaseData->getEmail(),
            [
                'token' => $token,
                'identifier' => $identifier,
                'expire' => date($this->settings['date_format'], $expiresAt),
                'engines' => $engines,
            ]
        );
    }

    public function purchaseInfo($action, $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $engines = htmlspecialchars($data['extra_data']['engines']);

        if ($action == "email") {
            return $this->template->render(
                "services/shopsms_license/purchase_info_email",
                compact('data', 'engines'),
                true,
                false
            );
        }

        if ($action == "web") {
            $email = htmlspecialchars($data['email']);
            return $this->template->render(
                "services/shopsms_license/purchase_info_web",
                compact('data', 'engines', 'email') + ['serviceName' => $this->service['name']],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->sprintf(
                    $this->lang->translate('license_bought'),
                    $data['amount']
                ),
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
        return $this->template->render(
            "services/shopsms_license/user_service_admin_add",
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
        return [
            'status' => 'ok',
            'text' => $this->lang->translate('service_added_correctly'),
            'positive' => true,
        ];
    }

    // ------------------- My Current Services --------------------

    public function userOwnServiceInfoGet($userService, $buttonEdit)
    {
        $identifier = htmlspecialchars($userService['identifier']);
        $expire =
            $userService['expire'] != '-1'
                ? date($this->settings['date_format'], $userService['expire'])
                : $this->lang->translate('never');
        $email = strlen($userService['email'])
            ? htmlspecialchars($userService['email'])
            : $this->lang->translate('none');
        $cost_monthly = number_format(($userService['cost_daily'] * 30) / 100, 2);

        // Dostępne silniki
        $engines = [];
        if ($userService['platform_amxmodx']) {
            $engines[] = "AMX Mod X";
        }
        if ($userService['platform_sourcemod']) {
            $engines[] = "SOURCEMOD";
        }

        if (!empty($engines)) {
            $engines = implode(", ", $engines);
        } else {
            $engines = $this->lang->translate('none');
        }

        return $this->template->render(
            "services/shopsms_license/user_own_service",
            compact(
                'userService',
                'identifier',
                'engines',
                'email',
                'expire',
                'cost_monthly',
                'buttonEdit'
            ) + [
                'moduleId' => $this->getModuleId(),
                'serviceName' => $this->service['name'],
            ]
        );
    }

    public function userOwnServiceEditFormGet($userService)
    {
        $identifier = htmlspecialchars($userService['identifier']);
        $expire =
            $userService['expire'] != '-1'
                ? date($this->settings['date_format'], $userService['expire'])
                : $this->lang->translate('never');
        $email = htmlspecialchars($userService['email']);
        $cost_monthly = number_format(($userService['cost_daily'] * 30) / 100, 2);

        $engines = [
            'amxx' => [
                'input' => $userService['platform_amxmodx'] ? "1" : "0",
                'div' => $userService['platform_amxmodx'] ? "active" : "",
            ],
            'sm' => [
                'input' => $userService['platform_sourcemod'] ? "1" : "0",
                'div' => $userService['platform_sourcemod'] ? "active" : "",
            ],
        ];

        return $this->template->render(
            "services/shopsms_license/user_own_service_edit",
            compact('identifier', 'expire', 'email', 'engines', 'cost_monthly') + [
                'serviceId' => $this->service['id'],
                'serviceName' => $this->service['name'],
            ]
        );
    }

    public function userOwnServiceEdit($post, $userService)
    {
        // Wybranie przynajmniej jednego silnika gry
        if ($post['platform_amxmodx'] == "0" && $post['platform_sourcemod'] == '0') {
            $warnings['engines'][] = $this->lang->translate('no_engine_choosen');
        }

        // E-mail
        $post['email'] = trim($post['email']);
        if ($warning = check_for_warnings("email", $post['email'])) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Hasło
        if (
            strlen($post['password']) &&
            ($warning = check_for_warnings("password", $post['password']))
        ) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }

        $cost_data = $this->getCostUserEdit($post, $userService);

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
        $purchase_data->setService('ss_license_edit');
        $purchase_data->setOrder([
            'user_service_id' => $post['id'],
            'cost_daily' => $cost_data['cost_daily'],
            'bargain' => $cost_data['bargain'],
            'password' => $post['password'],
            'engines' => [
                'amxx' => $post['platform_amxmodx'],
                'sm' => $post['platform_sourcemod'],
            ],
        ]);
        $purchase_data->setPayment([
            'cost' => $cost_data['surcharge'] * $cost_data['bargain'],
            'no_sms' => true,
        ]);
        $purchase_data->setEmail($post['email']);

        $purchase_data_encoded = base64_encode(serialize($purchase_data));

        return [
            'status' => "payment",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'data' => [
                'data' => $purchase_data_encoded,
                'sign' => md5($purchase_data_encoded . $this->settings['random_key']),
            ],
        ];
    }

    public function serviceTakeOverFormGet()
    {
        return $this->template->render("services/shopsms_license/service_take_over");
    }

    public function serviceTakeOver($post)
    {
        // ID
        if (!strlen($post['token'])) {
            $warnings['token'][] = $this->lang->translate('field_empty');
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

        try {
            $response = $this->licenseServerService->getByToken($post['token']);
        } catch (Exception $e) {
            return [
                'status' => "no_service",
                'text' => $this->lang->translate('no_user_service'),
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
                [$post['service'], $response['id']]
            )
        );

        $row = $this->db->fetch_array_assoc($result);
        $user_service_id = $row['us_id'];

        $user = $this->auth->user();
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                TABLE_PREFIX .
                "user_service` " .
                "SET `uid` = '%d' " .
                "WHERE `id` = '%d'",
                [$user->getUid(), $user_service_id]
            )
        );

        if (!$this->db->affected_rows()) {
            return [
                'status' => "service_not_taken_over",
                'text' => $this->lang->translate('service_not_taken_over'),
                'positive' => false,
            ];
        }

        return [
            'status' => "ok",
            'text' => $this->lang->translate('service_taken_over'),
            'positive' => true,
        ];
    }

    public function userServiceDelete($userService, $who)
    {
        // Nie usuwaj, jezeli jest mniej niz 30 dni po wygasnieciu
        if (
            $who != 'admin' &&
            isset($userService['now']) &&
            $userService['expire'] + 60 * 60 * 24 * 30 > $userService['now']
        ) {
            return false;
        }

        $this->licenseServerService->delete($userService['external_license_id']);

        return true;
    }

    public function actionExecute($action, $body)
    {
        if ($action === "get_cost") {
            if ($body['amount'] < 30) {
                return $this->lang->translate('none');
            }

            return number_format(
                    $this->getCost($this->getCostDaily($body), $body['amount'], true) / 100,
                    2
                ) .
                ' ' .
                $this->settings['currency'];
        }

        if ($action === "get_cost_user_edit") {
            $cost_data = $this->getCostUserEdit(
                $body,
                get_users_services($body['user_service_id'])
            );

            $cost_data['surcharge'] =
                number_format(($cost_data['surcharge'] * $cost_data['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];
            $cost_data['cost_monthly'] =
                number_format(($cost_data['cost_monthly'] * $cost_data['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];
            $cost_data['cost_daily'] =
                number_format(($cost_data['cost_daily'] * $cost_data['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];

            return json_encode($cost_data);
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

            if (!$this->db->num_rows($result)) {
                return 'Invalid identifier';
            }

            $row = $this->db->fetch_array_assoc($result);
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
     * @param array $post Dane $_POST formularza zakupu
     * @return int|null
     */
    private function getCostDaily($post)
    {
        $cost = $this::COST_SHOP_PER_DAY;
        $cost_engines = 0;
        if ($post['platform_amxmodx']) {
            $cost_engines += $this::COST_ENGINE_PER_DAY;
        }
        if ($post['platform_sourcemod']) {
            $cost_engines += $this::COST_ENGINE_PER_DAY;
        }

        // Dodajemy koszt za kolejne silniki
        $cost += max(0, $cost_engines - $this::COST_ENGINE_PER_DAY); // -5, bo pierwsza gra jest darmowa

        return ceil($cost);
    }

    /**
     * @param array $post Dane $_POST
     * @param array $userService Dane o usludze użytkownika
     * @return array
     */
    private function getCostUserEdit($post, $userService)
    {
        if (empty($userService)) {
            return null;
        }

        $daysLeft = ($userService['expire'] - time()) / (24 * 60 * 60);

        $engines = [
            'amxx' => [
                'old' => $userService['platform_amxmodx'],
                'new' => $post['platform_amxmodx'],
            ],
            'sm' => [
                'old' => $userService['platform_sourcemod'],
                'new' => $post['platform_sourcemod'],
            ],
        ];

        $additionalCost = 0;
        foreach ($engines as $engine => $engineData) {
            // Jezeli anulujemy wsparcie dla jakiegos silnika, to tracimy wszelkie znizki
            // i przeliczamy normalnie koszt jaki wychodzi
            if ($engineData['old'] && !$engineData['new']) {
                $post['amount'] = $daysLeft; // Tworzymy tak jakby zapytanie z formularza zakupu
                $costDaily = $this->getCostDaily($post);
                break;
            }

            // Jezeli dodajemy wsparcie dla nowego silnika, to dodajemy do dniowki
            if ($engineData['new'] && !$engineData['old']) {
                $additionalCost += $this::COST_ENGINE_PER_DAY;
            }
        }

        if (!isset($costDaily)) {
            $costDaily = $userService['cost_daily'] + $additionalCost;
        }

        return [
            'surcharge' => max(0, ($costDaily - $userService['cost_daily']) * $daysLeft),
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
