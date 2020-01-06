<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServerService;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServerRepository;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class ServerResource
{
    public function put(
        $serverId,
        Request $request,
        TranslationManager $translationManager,
        ServerService $serverService,
        ServerRepository $serverRepository,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        $name = $request->request->get('name');
        $ip = trim($request->request->get('ip'));
        $port = trim($request->request->get('port'));
        $smsPlatform = $request->request->get('sms_platform') ?: null;

        $serverService->validateBody($request->request->all());
        $serverRepository->update($serverId, $name, $ip, $port, $smsPlatform);
        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        $databaseLogger->logWithActor('log_server_admin_edit', $serverId);

        return new SuccessApiResponse($lang->t('server_edit'));
    }

    public function delete(
        $serverId,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        try {
            $deleted = $serverRepository->delete($serverId);
        } catch (PDOException $e) {
            if (get_error_code($e) === 1451) {
                return new ErrorApiResponse($lang->t('delete_server_constraint_fails'));
            }

            throw $e;
        }

        if ($deleted) {
            $databaseLogger->logWithActor('log_server_admin_delete', $serverId);
            return new SuccessApiResponse($lang->t('delete_server'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_server'), 0);
    }
}
