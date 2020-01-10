<?php
namespace App\Repositories;

use App\Models\BoughtService;
use App\System\Database;

class BoughtServiceRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "bought_services` WHERE `id` = '%d'",
                    [$id]
                )
            );

            if ($data = $result->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create(
        $uid,
        $method,
        $paymentId,
        $service,
        $server,
        $amount,
        $authData,
        $email,
        $extraData = []
    ) {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "bought_services` " .
                    "SET `uid` = '%d', `payment` = '%s', `payment_id` = '%s', `service` = '%s', " .
                    "`server` = '%d', `amount` = '%s', `auth_data` = '%s', `email` = '%s', `extra_data` = '%s'",
                [
                    $uid,
                    $method,
                    $paymentId,
                    $service,
                    $server,
                    $amount,
                    $authData,
                    $email,
                    json_encode($extraData),
                ]
            )
        );

        return $this->get($this->db->lastId());
    }

    private function mapToModel(array $data)
    {
        return new BoughtService(
            (int) $data['id'],
            $data['uid'],
            $data['payment'],
            $data['payment_id'],
            $data['service'],
            $data['server'],
            $data['amount'],
            $data['auth_data'],
            $data['email'],
            json_decode($data['extra_data'])
        );
    }
}
