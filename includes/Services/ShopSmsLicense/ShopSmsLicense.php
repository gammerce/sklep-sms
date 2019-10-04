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

    public function purchaseFormValidate($body)
    {
        // Wybranie przynajmniej jednego silnika gry
        if ($body['platform_amxmodx'] == "0" && $body['platform_sourcemod'] == "0") {
            $warnings['engines'][] = $this->lang->translate('no_engine_choosen');
        }

        // Ilość
        if ($warning = check_for_warnings("number", $body['amount'])) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        } elseif ($body['amount'] < 30) {
            $warnings['amount'][] = $this->lang->sprintf(
                $this->lang->translate('value_must_be_ge_than'),
                30
            );
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
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $costDaily = $this->getCostDaily($body);
        $purchaseData = new Purchase();
        $purchaseData->setOrder([
            'amount' => $body['amount'],
            'engines' => [
                'amxx' => $body['platform_amxmodx'],
                'sm' => $body['platform_sourcemod'],
            ],
            'cost_daily' => $costDaily,
        ]);
        $purchaseData->setEmail($body['email']);
        $purchaseData->setPayment([
            'cost' => $this->getCost($costDaily, $body['amount'], true),
            'no_sms' => true,
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $engines = [];
        $tmpEngines = $purchaseData->getOrder('engines');
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
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
        $costMonthly =
            number_format(($purchaseData->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings['currency'];

        return $this->template->render(
            "services/shopsms_license/order_details",
            compact('purchaseData', 'engines', 'costMonthly', 'email') + [
                'serviceName' => $this->service['name'],
                'serviceTag' => $this->service['tag'],
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        $tmpEngines = $purchaseData->getOrder('engines');
        $lifetime = $purchaseData->getOrder('amount') * 24 * 60 * 60;

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
                [$purchaseData->user->getUid(), $this->service['id'], $expiresAt]
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
                    $this->service['id'],
                    $identifier,
                    $externalLicenseId,
                    $purchaseData->getOrder('cost_daily'),
                    $purchaseData->getEmail(),
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
        $costMonthly = number_format(($userService['cost_daily'] * 30) / 100, 2);

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
                'costMonthly',
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
        $costMonthly = number_format(($userService['cost_daily'] * 30) / 100, 2);

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
            compact('identifier', 'expire', 'email', 'engines', 'costMonthly') + [
                'serviceId' => $this->service['id'],
                'serviceName' => $this->service['name'],
            ]
        );
    }

    public function userOwnServiceEdit(array $body, $userService)
    {
        // Wybranie przynajmniej jednego silnika gry
        if ($body['platform_amxmodx'] == "0" && $body['platform_sourcemod'] == '0') {
            $warnings['engines'][] = $this->lang->translate('no_engine_choosen');
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
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData = new Purchase();
        $purchaseData->setService('ss_license_edit');
        $purchaseData->setOrder([
            'user_service_id' => $body['id'],
            'cost_daily' => $costData['cost_daily'],
            'bargain' => $costData['bargain'],
            'password' => $body['password'],
            'engines' => [
                'amxx' => $body['platform_amxmodx'],
                'sm' => $body['platform_sourcemod'],
            ],
        ]);
        $purchaseData->setPayment([
            'cost' => $costData['surcharge'] * $costData['bargain'],
            'no_sms' => true,
        ]);
        $purchaseData->setEmail($body['email']);

        $purchaseDataEncoded = base64_encode(serialize($purchaseData));

        return [
            'status' => "payment",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'data' => [
                'data' => $purchaseDataEncoded,
                'sign' => md5($purchaseDataEncoded . $this->settings['random_key']),
            ],
        ];
    }

    public function serviceTakeOverFormGet()
    {
        return $this->template->render("services/shopsms_license/service_take_over");
    }

    public function serviceTakeOver($body)
    {
        // ID
        if (!strlen($body['token'])) {
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
            $response = $this->licenseServerService->getByToken($body['token']);
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
                [$body['service'], $response['id']]
            )
        );

        $row = $this->db->fetchArrayAssoc($result);
        $userServiceId = $row['us_id'];

        $user = $this->auth->user();
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                TABLE_PREFIX .
                "user_service` " .
                "SET `uid` = '%d' " .
                "WHERE `id` = '%d'",
                [$user->getUid(), $userServiceId]
            )
        );

        if (!$this->db->affectedRows()) {
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
            $costData = $this->getCostUserEdit(
                $body,
                get_users_services($body['user_service_id'])
            );

            $costData['surcharge'] =
                number_format(($costData['surcharge'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];
            $costData['cost_monthly'] =
                number_format(($costData['cost_monthly'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];
            $costData['cost_daily'] =
                number_format(($costData['cost_daily'] * $costData['bargain']) / 100, 2) .
                ' ' .
                $this->settings['currency'];

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

            if (!$this->db->numRows($result)) {
                return 'Invalid identifier';
            }

            $row = $this->db->fetchArrayAssoc($result);
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
        $costEngines = 0;
        if ($post['platform_amxmodx']) {
            $costEngines += $this::COST_ENGINE_PER_DAY;
        }
        if ($post['platform_sourcemod']) {
            $costEngines += $this::COST_ENGINE_PER_DAY;
        }

        // Dodajemy koszt za kolejne silniki
        $cost += max(0, $costEngines - $this::COST_ENGINE_PER_DAY); // -5, bo pierwsza gra jest darmowa

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
