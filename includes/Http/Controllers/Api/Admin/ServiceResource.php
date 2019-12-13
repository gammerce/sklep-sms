<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SqlQueryException;
use App\Http\Responses\ApiResponse;
use App\Http\Services\ServiceService;
use App\System\Auth;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceResource
{
    public function put(
        $serviceId,
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Database $db,
        Heart $heart,
        ServiceService $serviceService
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        // ID
        $id2 = $request->request->get('id2');
        $name = $request->request->get('name');
        $shortDescription = $request->request->get('short_description');
        $order = $request->request->get('order');
        $description = $request->request->get('description');
        $tag = $request->request->get('tag');
        $groups = $request->request->get('groups');

        $warnings = [];
        $set = "";

        $serviceModule = $heart->getServiceModule($id2);

        $serviceService->validateBody($request->request->all(), $warnings, $set, $serviceModule);

        if ($serviceId !== $id2) {
            // Sprawdzanie czy usługa o takim ID już istnieje
            if ($heart->getService($serviceId) !== null) {
                $warnings['id'][] = $lang->translate('id_exist');
            }
        }

        $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "services` " .
                    "SET `id` = '%s', `name` = '%s', `short_description` = '%s', `description` = '%s', " .
                    "`tag` = '%s', `groups` = '%s', `order` = '%d' " .
                    $set .
                    "WHERE `id` = '%s'",
                [
                    $id2,
                    $name,
                    $shortDescription,
                    $description,
                    $tag,
                    implode(";", $groups),
                    $order,
                    $serviceId,
                ]
            )
        );

        if ($db->affectedRows()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('service_admin_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceId
                )
            );
            return new ApiResponse('ok', $lang->translate('service_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('service_no_edit'), 0);
    }

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
            log_to_db(
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
