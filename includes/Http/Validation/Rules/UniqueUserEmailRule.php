<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Support\Database;

class UniqueUserEmailRule extends BaseRule
{
    /** @var Database */
    private $db;

    /** @var int */
    private $exceptUserId;

    public function __construct($exceptUserId = null)
    {
        parent::__construct();
        $this->db = app()->make(Database::class);
        $this->exceptUserId = $exceptUserId;
    }

    public function validate($attribute, $value, array $data)
    {
        $statement = $this->db->statement(
            "SELECT `uid` FROM `ss_users` WHERE `email` = ? AND `uid` != ?"
        );
        $statement->execute([$value, $this->exceptUserId]);

        if ($statement->rowCount()) {
            return [$this->lang->t('email_occupied')];
        }

        return [];
    }
}
