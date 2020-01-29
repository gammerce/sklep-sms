<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Http\Validation\BaseRule;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\Support\Database;

class ExtraFlagPasswordDiffersRule extends BaseRule
{
    /** @var Database */
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = app()->make(Database::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $table = ExtraFlagsServiceModule::USER_SERVICE_TABLE;
        $type = array_get($data, 'type');
        $serverId = array_get($data, 'server_id');
        $authData = array_get($data, 'auth_data');

        $statement = $this->db->statement(
            "SELECT `password` FROM `$table` " .
                "WHERE `type` = ? AND `auth_data` = ? AND `server` = ?"
        );
        $statement->execute([$type, $authData, $serverId]);
        $existingPassword = $statement->fetchColumn();

        // TODO: Usunąć md5 w przyszłości
        if (
            $existingPassword &&
            $existingPassword !== $value &&
            $existingPassword !== md5($value)
        ) {
            return [$this->lang->t('existing_service_has_different_password')];
        }

        return [];
    }
}
