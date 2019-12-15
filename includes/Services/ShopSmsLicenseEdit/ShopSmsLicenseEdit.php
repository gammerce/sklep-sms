<?php
namespace App\Services\ShopSmsLicenseEdit;

use App\LicenseServerService;
use App\Models\Purchase;
use App\Payment\BoughtServiceService;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
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

    /** @var BoughtServiceService */
    protected $boughtServiceService;

    public function __construct($service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
    }

    public function purchaseFormGet()
    {
        //
    }

    public function purchaseFormValidate($data)
    {
        //
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $identifier = $this->db->getColumn(
            $this->db->prepare(
                "SELECT `identifier` FROM `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `us_id` = '%d'",
                [$purchaseData->getOrder('user_service_id')]
            ),
            'identifier'
        );

        $email = if_strlen2($purchaseData->getEmail(true), $this->lang->translate('none'));
        $costMonthly =
            number_format(($purchaseData->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings['currency'];

        $engines = [];
        $tmpEngines = $purchaseData->getOrder('engines');
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->translate('none');

        return $this->template->render(
            "services/shopsms_license_edit/order_details",
            compact('identifier', 'engines', 'costMonthly', 'email') + [
                'serviceName' => $this->service['name'],
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        $userService = get_users_services($purchaseData->getOrder('user_service_id'));
        $tmpEngines = $purchaseData->getOrder('engines');

        $this->licenseServerService->updatePlatforms(
            $userService['external_license_id'],
            $tmpEngines['amxx'],
            $tmpEngines['sm']
        );

        // Aktualizujemy dane licencji w liscie uslug graczy
        $updateData = [
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
                'data' => [$tmpEngines['amxx']],
            ],
            [
                'column' => 'platform_sourcemod',
                'value' => "'%d'",
                'data' => [$tmpEngines['sm']],
            ],
        ];
        $this->updateUserService(
            $updateData,
            $purchaseData->getOrder('user_service_id'),
            $purchaseData->getOrder('user_service_id')
        );

        // Dodanie informacji o zakupie usługi
        $engines = [];
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->translate('none');

        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service['id'],
            0,
            0,
            $userService['identifier'],
            $purchaseData->getEmail(),
            ['engines' => $engines]
        );
    }

    public function purchaseInfo($action, $data)
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

    public function showOnWeb()
    {
        return false;
    }
}
