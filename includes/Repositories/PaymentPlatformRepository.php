<?php
namespace App\Repositories;

use App\Exceptions\EntityNotFoundException;
use App\Models\PaymentPlatform;
use App\Support\Database;

class PaymentPlatformRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $name
     * @param string $module
     * @param array $data
     * @return PaymentPlatform
     */
    public function create($name, $module, array $data = []): PaymentPlatform
    {
        $this->db
            ->statement(
                "INSERT INTO `ss_payment_platforms` SET `name` = ?, `module` = ?, `data` = ?"
            )
            ->execute([$name, $module, json_encode($data)]);

        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @param string $name
     * @param array $data
     */
    public function update($id, $name, array $data = []): void
    {
        $this->db
            ->statement("UPDATE `ss_payment_platforms` SET `name` = ?, `data` = ? WHERE `id` = ?")
            ->execute([$name, json_encode($data), $id]);
    }

    /**
     * @return PaymentPlatform[]
     */
    public function all(): array
    {
        $statement = $this->db->query("SELECT * FROM `ss_payment_platforms`");

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    /**
     * @param array $ids
     * @return PaymentPlatform[]
     */
    public function findMany(array $ids): array
    {
        $keys = implode(",", array_fill(0, count($ids), "?"));
        $statement = $this->db->statement(
            "SELECT * FROM `ss_payment_platforms` WHERE `id` IN ({$keys})"
        );
        $statement->execute($ids);

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    /**
     * @param int $id
     * @return PaymentPlatform|null
     */
    public function get($id): ?PaymentPlatform
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `ss_payment_platforms` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param int $id
     * @return PaymentPlatform
     * @throws EntityNotFoundException
     */
    public function getOrFail($id): PaymentPlatform
    {
        if ($paymentPlatform = $this->get($id)) {
            return $paymentPlatform;
        }

        throw new EntityNotFoundException();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_payment_platforms` WHERE `id` = ?");
        $statement->execute([$id]);
        return !!$statement->rowCount();
    }

    /**
     * @param array $data
     * @return PaymentPlatform
     */
    public function mapToModel(array $data): PaymentPlatform
    {
        return new PaymentPlatform(
            as_int($data["id"]),
            $data["name"],
            $data["module"],
            json_decode($data["data"], true)
        );
    }
}
