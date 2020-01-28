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

        $serviceModule = $heart->getServiceModule($serviceId);
        if (!($serviceModule instanceof IServiceUserServiceAdminAdd)) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        $serviceModule->userServiceAdminAdd($request->request->all());

        return [
            'status' => "ok",
            'text' => $lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }
}
