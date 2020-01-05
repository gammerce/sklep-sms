<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServerService;
use App\Repositories\ServerRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class ServerResource
{
    public function put(
        $serverId,
        Request $request,
        Auth $auth,
        TranslationManager $translationManager,
        ServerService $serverService,
        ServerRepository $serverRepository
    ) {
        $langShop = $translationManager->shop();
        $lang = $translationManager->user();
        $user = $auth->user();

        $name = $request->request->get('name');
        $ip = trim($request->request->get('ip'));
        $port = trim($request->request->get('port'));
        // TODO Check if setting default value works
        $smsPlatform = $request->request->get('sms_platform') ?: null;

        $serverService->validateBody($request->request->all());
        $serverRepository->update($serverId, $name, $ip, $port, $smsPlatform);
        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('server_admin_edit'),
                $user->getUsername(),
                $user->getUid(),
                $serverId
            )
        );

        return new SuccessApiResponse($lang->translate('server_edit'));
    }

    public function delete(
        $serverId,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        try {
            $deleted = $serverRepository->delete($serverId);
        } catch (PDOException $e) {
            if (get_error_code($e) === 1451) {
                return new ErrorApiResponse($lang->t('delete_server_constraint_fails'));
            }

            throw $e;
        }

        if ($deleted) {
            log_to_db(
                $langShop->t(
                    'server_admin_delete',
                    $user->getUsername(),
                    $user->getUid(),
                    $serverId
                )
            );
            return new SuccessApiResponse($lang->t('delete_server'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_server'), 0);
    }
}
