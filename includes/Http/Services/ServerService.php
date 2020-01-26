<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Models\Service;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServiceAvailableOnServers;
use App\Services\ServerServiceService;
use App\System\Database;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServerService
{
    /** @var Database */
    private $db;

    /** @var Translator */
    private $lang;

    /** @var Heart */
    private $heart;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var Settings */
    private $settings;

    /** @var ServerServiceService */
    private $serverServiceService;

    public function __construct(
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServerServiceService $serverServiceService,
        Settings $settings
    ) {
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->heart = $heart;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->settings = $settings;
        $this->serverServiceService = $serverServiceService;
    }

    public function validateBody(array $body)
    {
        $name = array_get($body, 'name');
        $ip = array_get($body, 'ip');
        $port = array_get($body, 'port');
        $smsPlatformId = as_int(array_get($body, 'sms_platform'));

        $warnings = [];

        if (!$name) {
            $warnings['name'][] = $this->lang->t('field_no_empty');
        }

        if (!$ip) {
            $warnings['ip'][] = $this->lang->t('field_no_empty');
        }

        if (!$port) {
            $warnings['port'][] = $this->lang->t('field_no_empty');
        }

        if ($smsPlatformId && !$this->paymentPlatformRepository->get($smsPlatformId)) {
            $warnings['sms_platform'][] = $this->lang->t('no_sms_platform');
        }

        if (!$smsPlatformId && !$this->settings->getSmsPlatformId()) {
            $warnings['sms_platform'][] = $this->lang->t('no_default_sms_platform');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }

    public function updateServerServiceAffiliations($serverId, array $body)
    {
        $serversServices = collect($this->heart->getServices())
            ->filter(function (Service $service) {
                // This service can be bought on this server
                return $this->heart->getServiceModule($service->getId()) instanceof
                    IServiceAvailableOnServers;
            })
            ->map(function (Service $service) use ($serverId, $body) {
                return [
                    'service_id' => $service->getId(),
                    'server_id' => $serverId,
                    'connect' => (bool) array_get($body, $service->getId()),
                ];
            })
            ->all();

        $this->serverServiceService->updateAffiliations($serversServices);
    }
}
