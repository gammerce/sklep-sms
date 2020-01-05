<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
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
        $smsPlatformId = $request->request->get('sms_platform') ?: null;

        $serverService->validateBody($request->request->all());

        $server = $serverRepository->create($name, $ip, $port, $smsPlatformId);
        $serverId = $server->getId();

        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        log_to_db(
            $langShop->t('server_admin_add', $user->getUsername(), $user->getUid(), $serverId)
        );

        return new SuccessApiResponse($lang->t('server_added'), [
            "data" => [
                "id" => $server->getId(),
            ],
        ]);
    }
}
