<?php
namespace App\Controllers\Api\Admin;

use App\Heart;
use App\Responses\ApiResponse;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\TranslationManager;
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
            return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
        }

        $returnData = $serviceModule->userServiceAdminAdd($request->request->all());

        // Przerabiamy ostrzeżenia, aby lepiej wyglądały
        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }
}
