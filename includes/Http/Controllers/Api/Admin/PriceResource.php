<?php
namespace App\Http\Controllers\Api\Admin;

use App\System\Auth;
use App\System\Database;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;

class PriceResource
{
    public function put($priceId)
    {
        // TODO Implement
    }

    public function delete(
        $priceId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "pricelist` WHERE `id` = '%d'", [
                $priceId,
            ])
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('price_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $priceId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_price'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_price'), 0);
    }
}
