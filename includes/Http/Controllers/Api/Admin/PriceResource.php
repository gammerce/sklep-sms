<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Services\PriceService;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PriceResource
{
    public function put(
        $priceId,
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        PriceService $priceService,
        Database $db
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $service = $request->request->get('service');
        $server = $request->request->get('server');
        $tariff = $request->request->get('tariff');
        $amount = $request->request->get('amount');

        $priceService->validateBody($request->request->all());

        $statement = $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "pricelist` " .
                    "SET `service` = '%s', `tariff` = '%d', `amount` = '%d', `server` = '%d' " .
                    "WHERE `id` = '%d'",
                [$service, $tariff, $amount, $server, $priceId]
            )
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('price_admin_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $priceId
                )
            );
            return new ApiResponse('ok', $lang->translate('price_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('price_no_edit'), 0);
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

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "pricelist` WHERE `id` = '%d'", [
                $priceId,
            ])
        );

        if ($statement->rowCount()) {
            log_to_db(
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
