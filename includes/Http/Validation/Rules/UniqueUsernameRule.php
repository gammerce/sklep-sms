<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Support\Database;

class UniqueUsernameRule extends BaseRule
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
        $warnings = check_for_warnings("username", $value);

        $statement = $this->db->statement(
            "SELECT `uid` FROM `ss_users` WHERE `username` = ? AND `uid` != ?"
        );
        $statement->execute([$value, $this->exceptUserId]);

        if ($statement->rowCount()) {
            $warnings[] = $this->lang->t('nick_occupied');
        }

        return $warnings;
    }
}
