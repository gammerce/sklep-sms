<?php
namespace App\License\ServiceModules\ShopSmsLicense\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\License\ServiceModules\ShopSmsLicense\LicenseServiceModule;
use App\Support\Database;

class LicenseProlongableRule extends BaseRule
{
    private Database $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = app()->make(Database::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        $table = LicenseServiceModule::USER_SERVICE_TABLE;

        $statement = $this->db->statement(
            "SELECT 1 FROM `ss_user_service` AS us " .
                "INNER JOIN `$table` AS m ON m.us_id = us.id " .
                "WHERE m.identifier = ? AND us.expire != '-1'"
        );
        $statement->bindAndExecute([$value]);

        if (!$statement->rowCount()) {
            throw new ValidationException($this->lang->t("wrong_license_data"));
        }
    }
}
