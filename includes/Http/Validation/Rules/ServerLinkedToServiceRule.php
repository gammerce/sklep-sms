<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Models\Service;

class ServerLinkedToServiceRule extends BaseRule
{
    private ServerServiceManager $serverServiceManager;
    private ServerManager $serverManager;
    private Service $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->serverServiceManager = app()->make(ServerServiceManager::class);
        $this->serverManager = app()->make(ServerManager::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        $server = $this->serverManager->get($value);

        if (
            !$server ||
            !$this->serverServiceManager->serverServiceLinked(
                $server->getId(),
                $this->service->getId()
            )
        ) {
            throw new ValidationException($this->lang->t("chosen_incorrect_server"));
        }
    }
}
