<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Responses\ApiResponse;
use App\TranslationManager;

class GroupResource
{
    public function delete($groupId, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "groups` WHERE `id` = '%d'", [
                $groupId,
            ])
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('group_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $groupId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_group'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_group'), 0);
    }
}
