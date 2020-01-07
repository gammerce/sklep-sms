<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class TariffResource
{
    public function put(
        $tariffId,
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $provision = $request->request->get('provision');

        $warnings = [];

        // Prowizja
        if ($warning = check_for_warnings("number", $provision)) {
            $warnings['provision'] = array_merge((array) $warnings['provision'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $statement = $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "tariffs` " .
                    "SET `provision` = '%d' " .
                    "WHERE `id` = '%d'",
                [$provision * 100, $tariffId]
            )
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->t('tariff_admin_edit', $user->getUsername(), $user->getUid(), $tariffId)
            );
            return new SuccessApiResponse($lang->t('tariff_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('tariff_no_edit'), 0);
    }

    public function delete(
        $tariffId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $statement = $db->query(
            $db->prepare(
                "DELETE FROM `" .
                    TABLE_PREFIX .
                    "tariffs` WHERE `id` = '%d' AND `predefined` = '0'",
                [$tariffId]
            )
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->t(
                    'tariff_admin_delete',
                    $user->getUsername(),
                    $user->getUid(),
                    $tariffId
                )
            );
            return new SuccessApiResponse($lang->t('delete_tariff'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_tariff'), 0);
    }
}
