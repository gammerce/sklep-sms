<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\ServiceModules\MybbExtraGroups\MybbRepository;

class MybbUserExistsRule extends BaseRule
{
    private MybbRepository $mybbRepository;

    public function __construct(MybbRepository $mybbRepository)
    {
        parent::__construct();
        $this->mybbRepository = $mybbRepository;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->mybbRepository->existsByUsername($value)) {
            throw new ValidationException($this->lang->t("no_user"));
        }
    }
}
