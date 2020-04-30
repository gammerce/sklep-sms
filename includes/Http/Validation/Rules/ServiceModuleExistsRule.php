<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Managers\ServiceModuleManager;

class ServiceModuleExistsRule extends BaseRule
{
    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct()
    {
        parent::__construct();
        $this->serviceModuleManager = app()->make(ServiceModuleManager::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $serviceModule = $this->serviceModuleManager->getEmpty($value);

        if (!$serviceModule) {
            return [$this->lang->t('wrong_module')];
        }

        return [];
    }
}
