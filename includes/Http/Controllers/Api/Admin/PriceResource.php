<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PriceService;
use App\Loggers\DatabaseLogger;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PriceResource
{
    public function put(
        $priceId,
        Request $request,
        TranslationManager $translationManager,
        PriceService $priceService,
        Database $db,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

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
            $logger->logWithActor('log_price_edited', $priceId);
            return new SuccessApiResponse($lang->t('price_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('price_no_edit'), 0);
    }

    public function delete(
        $priceId,
        Database $db,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "pricelist` WHERE `id` = '%d'", [
                $priceId,
            ])
        );

        if ($statement->rowCount()) {
            $logger->logWithActor('log_price_deleted', $priceId);
            return new SuccessApiResponse($lang->t('delete_price'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_price'), 0);
    }
}
