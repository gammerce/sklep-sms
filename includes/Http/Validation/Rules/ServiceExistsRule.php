<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\ServerRepository;
use App\Repositories\ServiceRepository;

class ServiceExistsRule extends BaseRule
{
    /** @var ServiceRepository */
    private $serviceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->serviceRepository = app()->make(ServiceRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->serviceRepository->get($value)) {
            return [$this->lang->t('no_such_service')];
        }

        return [];
    }
}
