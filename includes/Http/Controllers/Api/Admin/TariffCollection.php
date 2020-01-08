<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class TariffCollection
{
    public function post(
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $id = $request->request->get("id");
        $provision = $request->request->get("provision");

        $warnings = [];

        // Taryfa
        if ($warning = check_for_warnings("number", $id)) {
            $warnings['id'] = array_merge((array) $warnings['id'], $warning);
        }
        if ($heart->getTariff($id) !== null) {
            $warnings['id'][] = $lang->t('tariff_exist');
        }

        // Prowizja
        if ($warning = check_for_warnings("number", $provision)) {
            $warnings['provision'] = array_merge((array) $warnings['provision'], $warning);
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

        $logger->logWithActor('log_tariff_added', $db->lastId());

        return new SuccessApiResponse($lang->t('tariff_add'));
    }
}
