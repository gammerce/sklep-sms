<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Repositories\PaymentPlatformRepository;
use App\Services\Interfaces\IServiceAvailableOnServers;
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

    public function __construct(
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        PaymentPlatformRepository $paymentPlatformRepository,
        Settings $settings
    ) {
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->heart = $heart;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->settings = $settings;
    }

    public function validateBody(array $body)
    {
        $name = array_get($body, 'name');
        $ip = array_get($body, 'ip');
        $port = array_get($body, 'port');
        $smsPlatformId = array_get($body, 'sms_platform');

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
        $serversServices = [];
        foreach ($this->heart->getServices() as $service) {
            $serviceModule = $this->heart->getServiceModule($service->getId());

            // This service can be bought on this server
            if ($serviceModule instanceof IServiceAvailableOnServers) {
                $serversServices[] = [
                    'service' => $service->getId(),
                    'server' => $serverId,
                    'status' => (bool) array_get($body, $service->getId()),
                ];
            }
        }

        $this->updateServersServices($serversServices);
    }

    /**
     * Updates servers_services table
     *
     * @param $data
     */
    private function updateServersServices(array $data)
    {
        $delete = [];
        $add = [];
        foreach ($data as $arr) {
            if ($arr['status']) {
                $add[] = $this->db->prepare("('%d', '%s')", [$arr['server'], $arr['service']]);
            } else {
                $delete[] = $this->db->prepare("(`server_id` = '%d' AND `service_id` = '%s')", [
                    $arr['server'],
                    $arr['service'],
                ]);
            }
        }

        if (!empty($add)) {
            $this->db->query(
                "INSERT IGNORE INTO `" .
                    TABLE_PREFIX .
                    "servers_services` (`server_id`, `service_id`) " .
                    "VALUES " .
                    implode(", ", $add)
            );
        }

        if (!empty($delete)) {
            $this->db->query(
                "DELETE FROM `" .
                    TABLE_PREFIX .
                    "servers_services` " .
                    "WHERE " .
                    implode(" OR ", $delete)
            );
        }
    }
}
