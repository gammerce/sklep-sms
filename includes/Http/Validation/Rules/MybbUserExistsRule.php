<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Support\Database;

class MybbUserExistsRule extends BaseRule
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    public function validate($attribute, $value, array $data)
    {
        $statement = $this->db->statement("SELECT 1 FROM `mybb_users` WHERE `username` = ?");
        $statement->execute([$value]);

        if (!$statement->rowCount()) {
            return [$this->lang->t('no_user')];
        }

        return [];
    }
}
