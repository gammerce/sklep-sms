<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Responses\ApiResponse;
use App\TranslationManager;

class TariffResource
{
    public function delete($tariffId, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "tariffs` WHERE `id` = '%d' AND `predefined` = '0'",
                [$tariffId]
            )
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('tariff_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $tariffId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_tariff'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_tariff'), 0);
    }
}