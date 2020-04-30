<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\GroupsRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\ServiceModule;

class ServiceService
{
    public function extendValidator(Validator $validator, ServiceModule $serviceModule = null)
    {
        $validator->extendData([
            "groups" => $validator->getData("groups") ?: [],
            "order" => trim($validator->getData("order")),
        ]);

        $validator->extendRules([
            "groups" => [new GroupsRule()],
            "name" => [new RequiredRule()],
            "order" => [new IntegerRule()],
            "short_description" => [new MaxLengthRule(28)],
            "description" => [],
            "tag" => [],
        ]);

        if ($serviceModule instanceof IServiceAdminManage) {
            $serviceModule->serviceAdminManagePre($validator);
        }

        return $validator;
    }
}
