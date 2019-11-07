<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class TariffCollection
{
    public function post(Request $request, Database $db, TranslationManager $translationManager, Auth $auth, Heart $heart)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $id = $request->request->get("id");
        $provision = $request->request->get("provision");

        $warnings = [];

        // Taryfa
        if ($warning = check_for_warnings("number", $id)) {
            $warnings['id'] = array_merge((array)$warnings['id'], $warning);
        }
        if ($heart->getTariff($id) !== null) {
            $warnings['id'][] = $lang->translate('tariff_exist');
        }

        // Prowizja
        if ($warning = check_for_warnings("number", $provision)) {
            $warnings['provision'] = array_merge((array)$warnings['provision'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $db->query(
            $db->prepare(
                "INSERT INTO `" .
                TABLE_PREFIX .
                "tariffs` " .
                "SET `id` = '%d', `provision` = '%d'",
                [$id, $provision * 100]
            )
        );

        log_info(
            $langShop->sprintf(
                $langShop->translate('tariff_admin_add'),
                $user->getUsername(),
                $user->getUid(),
                $db->lastId()
            )
        );

        return new ApiResponse('ok', $lang->translate('tariff_add'), 1);
    }
}
