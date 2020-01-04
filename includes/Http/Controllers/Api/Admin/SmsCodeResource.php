<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;

class SmsCodeResource
{
    public function delete(
        $smsCodeId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "sms_codes` WHERE `id` = '%d'", [
                $smsCodeId,
            ])
        );

        if ($statement->rowCount()) {
            log_to_db(
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
