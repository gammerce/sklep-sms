<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
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
                $langShop->t§(
                    'sms_code_admin_delete',
                    $user->getUsername(),
                    $user->getUid(),
                    $smsCodeId
                )
            );
            return new SuccessApiResponse($lang->t('delete_sms_code'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_sms_code'), 0);
    }
}
