<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Responses\ApiResponse;
use App\TranslationManager;

class SmsCodeResource
{
    public function delete($smsCodeId, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "sms_codes` WHERE `id` = '%d'", [$smsCodeId])
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('sms_code_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $smsCodeId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_sms_code'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_sms_code'), 0);
    }
}