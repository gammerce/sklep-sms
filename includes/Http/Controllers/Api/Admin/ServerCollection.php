<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Services\ServerService;
use App\Repositories\ServerRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServerCollection
{
    public function post(
        Request $request,
        Auth $auth,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        ServerService $serverService
    ) {
        $langShop = $translationManager->shop();
        $lang = $translationManager->user();
        $user = $auth->user();

        $name = $request->request->get('name');
        $ip = trim($request->request->get('ip'));
        $port = trim($request->request->get('port'));
        $smsService = $request->request->get('sms_service');

        $serverService->validateBody($request->request->all());

        $server = $serverRepository->create($name, $ip, $port, $smsService);
        $serverId = $server->getId();

        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        log_info(
            $langShop->sprintf(
                $langShop->translate('server_admin_add'),
                $user->getUsername(),
                $user->getUid(),
                $serverId
            )
        );
        return new ApiResponse('ok', $lang->translate('server_added'), 1);
    }
}
