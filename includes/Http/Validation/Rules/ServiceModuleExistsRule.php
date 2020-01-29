<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\System\Heart;

class ServiceModuleExistsRule extends BaseRule
{
    /** @var Heart */
    private $heart;

    public function __construct()
    {
        parent::__construct();
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $serviceModule = $this->heart->getEmptyServiceModule($value);

        if (!$serviceModule) {
            return [$this->lang->t('wrong_module')];
        }

        return [];
    }
}
