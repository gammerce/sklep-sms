<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\ServiceRepository;

class ServiceExistsRule extends BaseRule
{
    private ServiceRepository $serviceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = app()->make(ServiceRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->serviceRepository->get($value)) {
            throw new ValidationException($this->lang->t("no_such_service"));
        }
    }
}
