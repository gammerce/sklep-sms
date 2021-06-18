<?php
namespace App\Repositories;

use App\Models\Template;
use App\Support\Database;

class TemplateRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $id
     * @return Template|null
     */
    public function get($id): ?Template
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_templates` WHERE `id` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $theme
     * @param string $name
     * @return Template|null
     */
    public function find($theme, $name): ?Template
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_templates` WHERE `theme` = ? AND `name` = ?"
        );
        $statement->execute([$theme, $name]);
        $data = $statement->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $theme
     * @param string $name
     * @param string $content
     * @return Template
     */
    public function create($theme, $name, $content): Template
    {
        $this->db
            ->statement(
                <<<EOF
                INSERT INTO `ss_templates` 
                SET `theme` = ?, `name` = ?, `content` = ?, `created_at` = NOW(), `updated_at` = NOW()
EOF
            )
            ->execute([$theme, $name, $content]);

        return $this->get($this->db->lastId());
    }

    /**
     * @param int $id
     * @param string $content
     */
    public function update($id, $content): void
    {
        $this->db
            ->statement(
                <<<EOF
                UPDATE `ss_templates` 
                SET `content` = ?, `updated_at` = NOW()
                WHERE `id` = ?
EOF
            )
            ->execute([$content, $id]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_templates` WHERE `id` = ?");
        $statement->execute([$id]);
        return !!$statement->rowCount();
    }

    private function mapToModel(array $data): Template
    {
        return new Template(
            as_int($data["id"]),
            as_string($data["theme"]),
            as_string($data["name"]),
            as_string($data["content"]),
            as_datetime($data["created_at"]),
            as_datetime($data["updated_at"])
        );
    }
}
