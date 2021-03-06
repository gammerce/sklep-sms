<?php
namespace App\ServiceModules\ExtraFlags\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Models\Service;

class ExtraFlagServiceTypesRule extends BaseRule
{
    private Service $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!($this->service->getTypes() & $value)) {
            throw new ValidationException($this->lang->t("chosen_incorrect_type"));
        }
    }
}
