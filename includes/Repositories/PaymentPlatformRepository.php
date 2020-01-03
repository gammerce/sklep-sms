<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\PaymentPlatform;
use App\Models\Server;
use App\System\Database;

class PaymentPlatformRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($name, $module, array $data = [])
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_platforms` " .
                    "SET `name` = '%s', `module` = '%s', `data` = '%s'",
                [$name, $module, json_encode($data)]
            )
        );

        return $this->get($this->db->lastId());
    }

    /**
     * @return PaymentPlatform[]
     */
    public function all()
    {
        $result = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "payment_platforms`");

        $platforms = [];
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $platforms[] = $this->mapToModel($row);
        }

        return $platforms;
    }

    public function get($id)
    {
        if ($id) {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "payment_platforms` WHERE `id` = '%d'",
                    [$id]
                )
            );

            if ($data = $this->db->fetchArrayAssoc($result)) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function getOrFail($id)
    {
        if ($paymentPlatform = $this->get($id)) {
            return $paymentPlatform;
        }

        throw new EntityNotFoundException();
    }

    public function delete($id)
    {
        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "payment_platforms` " . "WHERE `id` = '%d'",
                [$id]
            )
        );

        return !!$this->db->affectedRows();
    }

    public function mapToModel(array $data)
    {
        return new PaymentPlatform(
            intval($data['id']),
            $data['name'],
            $data['module'],
            json_decode($data['data'], true)
        );
    }
}
