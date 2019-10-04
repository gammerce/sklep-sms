<?php
namespace App\Services\ShopSmsLicenseEdit;

use App\LicenseServerService;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Settings;
use App\TranslationManager;
use App\Translator;
use UnexpectedValueException;

class ShopSmsLicenseEdit extends ShopSmsLicenseEditSimple implements
    IServicePurchase,
    IServicePurchaseWeb
{
    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    /** @var LicenseServerService */
    protected $licenseServerService;

    public function __construct($service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
    }

    public function purchase_form_get()
    {
        //
    }

    public function purchase_form_validate($data)
    {
        //
    }

    public function order_details($purchase_data)
    {
        $identifier = $this->db->get_column(
            $this->db->prepare(
                "SELECT `identifier` FROM `" .
                TABLE_PREFIX .
                $this::USER_SERVICE_TABLE .
                "` " .
                "WHERE `us_id` = '%d'",
                [$purchase_data->getOrder('user_service_id')]
            ),
            'identifier'
        );

        $email = if_strlen2($purchase_data->getEmail(true), $this->lang->translate('none'));
        $cost_monthly =
            number_format(($purchase_data->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings['currency'];

        $engines = [];
        $tmp_engines = $purchase_data->getOrder('engines');
        if ($tmp_engines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmp_engines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->translate('none');

        return $this->template->render(
            "services/shopsms_license_edit/order_details",
            compact('identifier', 'engines', 'cost_monthly', 'email') + [
                'serviceName' => $this->service['name'],
            ],
            true,
            false
        );
    }

    public function purchase($purchaseData)
    {
        $user_service = get_users_services($purchaseData->getOrder('user_service_id'));
        $tmp_engines = $purchaseData->getOrder('engines');

        $this->licenseServerService->updatePlatforms(
            $user_service['external_license_id'],
            $tmp_engines['amxx'],
            $tmp_engines['sm']
        );

        // Aktualizujemy dane licencji w liscie uslug graczy
        $update_data = [
            [
                'column' => 'cost_daily',
                'value' => "'%d'",
                'data' => [$purchaseData->getOrder('cost_daily')],
            ],
            [
                'column' => 'email',
                'value' => "'%s'",
                'data' => [$purchaseData->getEmail()],
            ],
            [
                'column' => 'platform_amxmodx',
                'value' => "'%d'",
                'data' => [$tmp_engines['amxx']],
            ],
            [
                'column' => 'platform_sourcemod',
                'value' => "'%d'",
                'data' => [$tmp_engines['sm']],
            ],
        ];
        $this->update_user_service(
            $update_data,
            $purchaseData->getOrder('user_service_id'),
            $purchaseData->getOrder('user_service_id')
        );

        // Dodanie informacji o zakupie usługi
        $engines = [];
        if ($tmp_engines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmp_engines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->translate('none');

        return add_bought_service_info(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service['id'],
            0,
            0,
            $user_service['identifier'],
            $purchaseData->getEmail(),
            ['engines' => $engines]
        );
    }

    public function purchase_info($action, $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $engines = htmlspecialchars($data['extra_data']['engines']);

        if ($action == "email") {
            return $this->template->render(
                "services/shopsms_license_edit/purchase_info_email",
                compact('data', 'engines'),
                true,
                false
            );
        }

        if ($action == "web") {
            $email = htmlspecialchars($data['email']);
            return $this->template->render(
                "services/shopsms_license_edit/purchase_info_web",
                compact('data', 'email', 'engines'),
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->sprintf(
                    $this->lang->translate('license_edited'),
                    $data['auth_data']
                ),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function show_on_web()
    {
        return false;
    }
}
