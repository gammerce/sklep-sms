<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Models\Service;

class ServerLinkedToServiceRule extends BaseRule
{
    /** @var ServerServiceManager */
    private $serverServiceManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var Service */
    private $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->serverServiceManager = app()->make(ServerServiceManager::class);
        $this->serverManager = app()->make(ServerManager::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $server = $this->serverManager->getServer($value);

        if (
            !$server ||
            !$this->serverServiceManager->serverServiceLinked(
                $server->getId(),
                $this->service->getId()
            )
        ) {
            return [$this->lang->t("chosen_incorrect_server")];
        }

        return [];
    }
}
