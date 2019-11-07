<?php
namespace App\Http\Controllers\Api;

use App\System\Heart;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\PlainResponse;
use App\Services\Interfaces\IServiceTakeOver;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceTakeOverController
{
    public function post(
        $service,
        Request $request,
        Heart $heart,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        if (
            ($serviceModule = $heart->getServiceModule($service)) === null ||
            !($serviceModule instanceof IServiceTakeOver)
        ) {
            return new PlainResponse($lang->translate('bad_module'));
        }

        $returnData = $serviceModule->serviceTakeOver($request->request->all());

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
