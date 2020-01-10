<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\System\Heart;
use App\Translation\TranslationManager;

class ServiceTakeOverFormController
{
    public function get($service, Heart $heart, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        if (
            ($serviceModule = $heart->getServiceModule($service)) === null ||
            !($serviceModule instanceof IServiceTakeOver)
        ) {
            return new PlainResponse($lang->t('bad_module'));
        }

        return new PlainResponse($serviceModule->serviceTakeOverFormGet());
    }
}
