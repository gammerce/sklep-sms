<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\ServerManager;

class ServerExistsRule extends BaseRule
{
    private ServerManager $serverManager;

    public function __construct()
    {
        parent::__construct();
        $this->serverManager = app()->make(ServerManager::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->serverManager->get($value)) {
            throw new ValidationException($this->lang->t("no_server_id"));
        }
    }
}
