<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\ServiceRepository;

class ServiceNotExistsRule extends BaseRule
{
    private ?string $exceptServiceId;
    private ServiceRepository $serviceRepository;

    public function __construct($exceptServiceId = null)
    {
        parent::__construct();
        $this->serviceRepository = app()->make(ServiceRepository::class);
        $this->exceptServiceId = $exceptServiceId;
    }

    public function validate($attribute, $value, array $data)
    {
        if ($value !== $this->exceptServiceId && $this->serviceRepository->get($value)) {
            throw new ValidationException($this->lang->t("id_exist"));
        }
    }
}
