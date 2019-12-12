<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SqlQueryException;
use App\Http\Responses\ApiResponse;
use App\Http\Services\ServerService;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServerResource
{
    public function put(
        $serverId,
        Request $request,
        Database $db,
        Auth $auth,
        TranslationManager $translationManager,
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

        $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "servers` " .
                    "SET `name` = '%s', `ip` = '%s', `port` = '%s', `sms_service` = '%s' " .
                    "WHERE `id` = '%s'",
                [$name, $ip, $port, $smsService, $serverId]
            )
        );

        $serverService->updateServerServiceAffiliations($serverId, $request->request->all());

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('server_admin_edit'),
                $user->getUsername(),
                $user->getUid(),
                $serverId
            )
        );
        return new ApiResponse('ok', $lang->translate('server_edit'), 1);
    }

    public function delete(
        $serverId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        try {
            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "servers` WHERE `id` = '%s'", [
                    $serverId,
                ])
            );
        } catch (SqlQueryException $e) {
            if ($e->getErrorno() == 1451) {
                // Istnieją powiązania
                return new ApiResponse(
                    "error",
                    $lang->translate('delete_server_constraint_fails'),
                    0
                );
            }

            throw $e;
        }

        if ($db->affectedRows()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('server_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serverId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_server'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_server'), 0);
    }
}
