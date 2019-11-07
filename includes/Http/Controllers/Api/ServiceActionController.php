<?php
namespace App\Http\Controllers\Api;

use App\System\Heart;
use App\Http\Responses\PlainResponse;
use App\Services\Interfaces\IServiceActionExecute;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceActionController
{
    public function post(
        $service,
        $action,
        Request $request,
        Heart $heart,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        if (
            ($serviceModule = $heart->getServiceModule($service)) === null ||
            !($serviceModule instanceof IServiceActionExecute)
        ) {
            return new PlainResponse($lang->translate('bad_module'));
        }

        return new PlainResponse($serviceModule->actionExecute($action, $request->request->all()));
    }
}
