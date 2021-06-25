<?php
namespace App\Theme;

use App\Models\Template;
use App\Support\Database;

class TemplateRepository
{
    const DEFAULT = "default";

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
     * @param string $name
     * @param string|null $theme
     * @param string|null $lang
     * @return Template|null
     */
    public function find($name, $theme, $lang): ?Template
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_templates` WHERE `name` = ? AND `theme` = ? AND `lang` = ?"
        );
        $statement->execute([$name, $theme ?? self::DEFAULT, $lang ?? self::DEFAULT]);
        $data = $statement->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $name
     * @param string|null $theme
     * @param string|null $lang
     * @param string $content
     * @return Template
     */
    public function create($name, $theme, $lang, $content): Template
    {
        $this->db
            ->statement(
                <<<EOF
                INSERT INTO `ss_templates` 
                SET `name` = ?, `theme` = ?, `lang` = ?, `content` = ?, `created_at` = NOW(), `updated_at` = NOW()
EOF
            )
            ->execute([$name, $theme ?? self::DEFAULT, $lang ?? self::DEFAULT, $content]);

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

    /**
     * @return string[]
     */
    public function listThemes(): array
    {
        $statement = $this->db->statement(
            <<<EOF
            SELECT `theme`
            FROM `ss_templates`
            WHERE `theme` != ?
            GROUP BY `theme`
            ORDER BY `theme` ASC
EOF
        );
        $statement->execute([self::DEFAULT]);

        return collect($statement)
            ->map(fn(array $row) => $row["theme"])
            ->all();
    }

    /**
     * @param string|null $theme
     * @param string|null $lang
     * @return Template[]
     */
    public function listTemplates($theme, $lang): array
    {
        $statement = $this->db->statement(
            <<<EOF
            SELECT * FROM `ss_templates`
            WHERE `theme` = ? AND `lang` = ?
            ORDER BY `name` ASC
EOF
        );
        $statement->execute([$theme ?? self::DEFAULT, $lang ?? self::DEFAULT]);

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    private function mapToModel(array $data): Template
    {
        return new Template(
            (int) $data["id"],
            (string) $data["name"],
            $data["theme"] === self::DEFAULT ? null : (string) $data["theme"],
            $data["lang"] === self::DEFAULT ? null : (string) $data["lang"],
            (string) $data["content"],
            as_datetime($data["created_at"]),
            as_datetime($data["updated_at"])
        );
    }
}
