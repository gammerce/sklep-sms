<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\System\Heart;
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
            return new PlainResponse($lang->t('bad_module'));
        }

        return new PlainResponse($serviceModule->actionExecute($action, $request->request->all()));
    }
}
