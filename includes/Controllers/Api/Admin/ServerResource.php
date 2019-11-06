<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Responses\ApiResponse;
use App\TranslationManager;

class ServerResource
{
    public function delete($serverId, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        try {
            $db->query(
                $db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "servers` WHERE `id` = '%s'",
                    [$serverId]
                )
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
            log_info(
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