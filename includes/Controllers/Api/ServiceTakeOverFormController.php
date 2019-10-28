<?php
namespace App\Controllers\Api;

use App\Heart;
use App\Responses\PlainResponse;
use App\Services\Interfaces\IServiceTakeOver;
use App\TranslationManager;

class ServiceTakeOverFormController
{
    public function get($service, Heart $heart, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        if (
            ($serviceModule = $heart->getServiceModule($service)) === null ||
            !($serviceModule instanceof IServiceTakeOver)
        ) {
            return new PlainResponse($lang->translate('bad_module'));
        }

        return new PlainResponse($serviceModule->serviceTakeOverFormGet());
    }
}