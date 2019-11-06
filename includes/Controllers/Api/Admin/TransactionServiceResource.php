<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class TransactionServiceResource
{
    public function put($transactionServiceId, Request $request, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $result = $db->query(
            $db->prepare(
                "SELECT data " .
                "FROM `" .
                TABLE_PREFIX .
                "transaction_services` " .
                "WHERE `id` = '%s'",
                [$transactionServiceId]
            )
        );
        $transactionService = $db->fetchArrayAssoc($result);
        $transactionService['data'] = json_decode($transactionService['data']);
        $arr = [];
        foreach ($transactionService['data'] as $key => $value) {
            $arr[$key] = $request->request->get($key);
        }

        $db->query(
            $db->prepare(
                "UPDATE `" .
                TABLE_PREFIX .
                "transaction_services` " .
                "SET `data` = '%s' " .
                "WHERE `id` = '%s'",
                [json_encode($arr), $transactionServiceId]
            )
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('payment_admin_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $transactionServiceId
                )
            );

            return new ApiResponse('ok', $lang->translate('payment_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('payment_no_edit'), 0);
    }
}