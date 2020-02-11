<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Models\Service;
use App\System\Heart;

class ServerLinkedToServiceRule extends BaseRule
{
    /** @var Heart */
    private $heart;

    /** @var Service */
    private $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $server = $this->heart->getServer($value);

        if (
            !$server ||
            !$this->heart->serverServiceLinked($server->getId(), $this->service->getId())
        ) {
            return [$this->lang->t('chosen_incorrect_server')];
        }

        return [];
    }
}
