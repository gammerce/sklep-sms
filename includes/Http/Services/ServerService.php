<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Repositories\PaymentPlatformRepository;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\System\Database;
use App\System\Heart;
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

    public function __construct(
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->heart = $heart;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    public function validateBody(array $body)
    {
        $name = array_get($body, 'name');
        $ip = array_get($body, 'ip');
        $port = array_get($body, 'port');
        $smsPlatform = array_get($body, 'sms_platform');

        $warnings = [];

        if (!$name) {
            $warnings['name'][] = $this->lang->translate('field_no_empty');
        }

        if (!$ip) {
            $warnings['ip'][] = $this->lang->translate('field_no_empty');
        }

        if (!$port) {
            $warnings['port'][] = $this->lang->translate('field_no_empty');
        }

        if ($smsPlatform && $this->paymentPlatformRepository->get($smsPlatform)) {
            $warnings['sms_platform'][] = $this->lang->translate('no_sms_platform');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }

    public function updateServerServiceAffiliations($serverId, array $body)
    {
        $serversServices = [];
        foreach ($this->heart->getServices() as $service) {
            // Dana usługa nie może być kupiona na serwerze
            $serviceModule = $this->heart->getServiceModule($service->getId());
            if (!($serviceModule instanceof IServiceAvailableOnServers)) {
                continue;
            }

            $serversServices[] = [
                'service' => $service->getId(),
                'server' => $serverId,
                'status' => (bool) $body[$service->getId()],
            ];
        }

        $this->updateServersServices($serversServices);
    }

    /**
     * Aktualizuje tabele servers_services
     *
     * @param $data
     */
    private function updateServersServices($data)
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
