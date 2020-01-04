<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\PaymentPlatform;
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
        $statement = $this->db->statement(
            "INSERT INTO `" .
                TABLE_PREFIX .
                "payment_platforms` " .
                "SET `name` = ?, `module` = ?, `data` = ?"
        );
        $statement->execute([$name, $module, json_encode($data)]);

        return $this->get($this->db->lastId());
    }

    /**
     * @return PaymentPlatform[]
     */
    public function all()
    {
        $statement = $this->db->statement("SELECT * FROM `" . TABLE_PREFIX . "payment_platforms`");
        $statement->execute();

        $platforms = [];
        while ($row = $statement->fetch()) {
            $platforms[] = $this->mapToModel($row);
        }

        return $platforms;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "payment_platforms` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
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
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "payment_platforms` WHERE `id` = ?"
        );
        $statement->execute([$id]);
        return !!$statement->rowCount();
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
