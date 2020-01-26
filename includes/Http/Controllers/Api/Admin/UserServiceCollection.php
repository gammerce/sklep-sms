<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceCollection
{
    public function post(
        $serviceId,
        Request $request,
        Heart $heart,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        if (
            ($serviceModule = $heart->getServiceModule($serviceId)) === null ||
            !($serviceModule instanceof IServiceUserServiceAdminAdd)
        ) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        $returnData = $serviceModule->userServiceAdminAdd($request->request->all());

        if ($returnData["status"] === "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        }

        return new ApiResponse(
            array_get($returnData, 'status'),
            array_get($returnData, 'text'),
            array_get($returnData, 'positive'),
            array_get($returnData, 'data')
        );
    }
}
