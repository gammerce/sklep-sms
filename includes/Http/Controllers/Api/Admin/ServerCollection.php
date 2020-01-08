<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServerService;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServerRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServerCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        ServerService $serverService,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        $name = $request->request->get('name');
        $ip = trim($request->request->get('ip'));
        $port = trim($request->request->get('port'));
        $smsPlatformId = $request->request->get('sms_platform') ?: null;

        $serverService->validateBody($request->request->all());

        $server = $serverRepository->create($name, $ip, $port, $smsPlatformId);
        $serverId = $server->getId();
        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        $databaseLogger->logWithActor('log_server_added', $serverId);

        return new SuccessApiResponse($lang->t('server_added'), [
            "data" => [
                "id" => $server->getId(),
            ],
        ]);
    }
}
