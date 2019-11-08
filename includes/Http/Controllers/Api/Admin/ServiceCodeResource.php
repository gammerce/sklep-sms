<?php
namespace App\Http\Controllers\Api\Admin;

use App\System\Auth;
use App\System\Database;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;

class ServiceCodeResource
{
    public function delete(
        $serviceCodeId,
        TranslationManager $translationManager,
        Database $db,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'", [
                $serviceCodeId,
            ])
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('code_deleted_admin'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceCodeId
                )
            );
            return new ApiResponse('ok', $lang->translate('code_deleted'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('code_not_deleted'), 0);
    }
}
