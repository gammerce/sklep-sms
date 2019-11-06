<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Heart;
use App\Responses\ApiResponse;
use App\TranslationManager;

class ServiceResource
{
    public function delete(
        $serviceId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth,
        Heart $heart
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $serviceModule = $heart->getServiceModule($serviceId);
        if (!is_null($serviceModule)) {
            $serviceModule->serviceDelete($serviceId);
        }

        try {
            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "services` WHERE `id` = '%s'", [
                    $serviceId,
                ])
            );
        } catch (SqlQueryException $e) {
            // It is affiliated with something
            if ($e->getErrorno() == 1451) {
                return new ApiResponse(
                    "error",
                    $lang->translate('delete_service_er_row_is_referenced_2'),
                    0
                );
            }

            throw $e;
        }
        $affected = $db->affectedRows();

        if ($affected) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('service_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_service'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_service'), 0);
    }
}
