<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Responses\ApiResponse;
use App\Responses\PlainResponse;
use App\Services\Interfaces\IServiceTakeOver;
use App\TranslationManager;
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
