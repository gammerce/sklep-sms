<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\ServiceModules\MybbExtraGroups\MybbRepository;

class MybbUserExistsRule extends BaseRule
{
    /** @var MybbRepository */
    private $mybbRepository;

    public function __construct(MybbRepository $mybbRepository)
    {
        parent::__construct();
        $this->mybbRepository = $mybbRepository;
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->mybbRepository->existsByUsername($value)) {
            return [$this->lang->t("no_user")];
        }

        return [];
    }
}
