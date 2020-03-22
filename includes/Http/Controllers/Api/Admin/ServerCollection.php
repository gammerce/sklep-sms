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

        $validator = $serverService->createValidator($request->request->all());
        $validated = $validator->validateOrFail();

        $server = $serverRepository->create(
            $validated['name'],
            $validated['ip'],
            $validated['port'],
            $validated['sms_platform'],
            $validated['transfer_platform']
        );
        $serverId = $server->getId();
        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());
        $databaseLogger->logWithActor('log_server_added', $serverId);

        return new SuccessApiResponse($lang->t('server_added'), [
            "data" => [
                "id" => $server->getId(),
                "token" => $server->getToken(),
            ],
        ]);
    }
}
