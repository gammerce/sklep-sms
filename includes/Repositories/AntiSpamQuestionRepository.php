<?php
namespace App\Repositories;

use App\Models\AntiSpamQuestion;
use App\Support\Database;

class AntiSpamQuestionRepository
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
            $statement = $this->db->statement(
                "SELECT * FROM `ss_antispam_questions` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findRandom()
    {
        $data = $this->db
            ->query("SELECT * FROM `ss_antispam_questions` ORDER BY RAND() LIMIT 1")
            ->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_antispam_questions` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function update($id, $question, $answers)
    {
        $statement = $this->db->statement(
            "UPDATE `ss_antispam_questions` " .
                "SET `question` = ?, `answers` = ? " .
                "WHERE `id` = ?"
        );
        $statement->execute([$question, $answers, $id]);
        return !!$statement->rowCount();
    }

    public function create($question, $answers)
    {
        $statement = $this->db->statement(
            "INSERT INTO `ss_antispam_questions` ( question, answers ) VALUES (?, ?)"
        );
        $statement->execute([$question, $answers]);
    }

    public function mapToModel(array $data)
    {
        return new AntiSpamQuestion($data['id'], $data['question'], explode(";", $data['answers']));
    }
}
