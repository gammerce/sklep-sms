<?php
namespace App\Repositories;

use App\Models\BoughtService;
use App\System\Database;

class BoughtServiceRepository
{
    /** * @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function get($id)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "bought_services` " . "WHERE `id` = '%d'",
                [$id]
            )
        );

        if (!$this->db->numRows($result)) {
            return null;
        }

        $entity = $this->db->fetchArrayAssoc($result);

        return new BoughtService(
            $entity['id'],
            $entity['uid'],
            $entity['payment'],
            $entity['payment_id'],
            $entity['service'],
            $entity['server'],
            $entity['amount'],
            $entity['auth_data'],
            $entity['email'],
            json_decode($entity['extra_data'])
        );
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

        $id = $this->db->lastId();

        return new BoughtService(
            $id,
            $uid,
            $method,
            $paymentId,
            $service,
            $server,
            $amount,
            $authData,
            $email,
            $extraData
        );
    }
}
