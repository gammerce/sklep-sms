<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
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

    public function __construct(Database $db, TranslationManager $translationManager, Heart $heart)
    {
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->heart = $heart;
    }

    public function validateBody(array $body)
    {
        $name = $body['name'];
        $ip = $body['ip'];
        $port = $body['port'];
        $smsService = $body['sms_service'];

        $warnings = [];

        // Nazwa
        if (!$name) {
            // Nie podano nazwy serwera
            $warnings['name'][] = $this->lang->translate('field_no_empty');
        }

        // IP
        if (!$ip) {
            // Nie podano nazwy serwera
            $warnings['ip'][] = $this->lang->translate('field_no_empty');
        }

        // Port
        if (!$port) {
            // Nie podano nazwy serwera
            $warnings['port'][] = $this->lang->translate('field_no_empty');
        }

        // Serwis płatności SMS
        if ($smsService) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT id " .
                        "FROM `" .
                        TABLE_PREFIX .
                        "transaction_services` " .
                        "WHERE `id` = '%s' AND sms = '1'",
                    [$smsService]
                )
            );
            if (!$this->db->numRows($result)) {
                $warnings['sms_service'][] = $this->lang->translate('no_sms_service');
            }
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
            if (
                !is_null($serviceModule = $this->heart->getServiceModule($service['id'])) &&
                !($serviceModule instanceof IServiceAvailableOnServers)
            ) {
                continue;
            }

            $serversServices[] = [
                'service' => $service['id'],
                'server' => $serverId,
                'status' => (bool) $body[$service['id']],
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
