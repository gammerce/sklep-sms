<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Responses\ApiResponse;
use App\Services\Interfaces\IServiceAdminManage;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCollection
{
    public function post(Request $request, TranslationManager $translationManager, Auth $auth, Database $db, Heart $heart)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        // ID
        $id = $_POST['id'];
        $id2 = $_POST['id2'];
        $name = $_POST['name'];
        $shortDescription = $_POST['short_description'];
        $order = $_POST['order'];
        $description = $_POST['description'];
        $tag = $_POST['tag'];
        $module = $_POST['module'];
        $groups = $_POST['groups'];

        $warnings = [];

        if (!strlen($id)) {
            // Nie podano id usługi
            $warnings['id'][] = $lang->translate('no_service_id');
        } else {
            if ($action == "service_add") {
                if (strlen($id) > 16) {
                    $warnings['id'][] = $lang->translate('long_service_id');
                }
            }
        }

        if (
            ($action == "service_add" && !isset($warnings['id'])) ||
            ($action == "service_edit" && $id !== $id2)
        ) {
            // Sprawdzanie czy usługa o takim ID już istnieje
            if ($heart->getService($id) !== null) {
                $warnings['id'][] = $lang->translate('id_exist');
            }
        }

        // Nazwa
        if (!strlen($name)) {
            $warnings['name'][] = $lang->translate('no_service_name');
        }

        // Opis
        if ($warning = check_for_warnings("service_description", $shortDescription)) {
            $warnings['short_description'] = array_merge(
                (array)$warnings['short_description'],
                $warning
            );
        }

        // Kolejność
        if (!my_is_integer($order)) {
            $warnings['order'][] = $lang->translate('field_integer');
        }

        // Grupy
        foreach ($groups as $group) {
            if (is_null($heart->getGroup($group))) {
                $warnings['groups[]'][] = $lang->translate('wrong_group');
                break;
            }
        }

        // Moduł usługi
        if ($action == "service_add") {
            if (($serviceModule = $heart->getServiceModuleS($module)) === null) {
                $warnings['module'][] = $lang->translate('wrong_module');
            }
        } else {
            $serviceModule = $heart->getServiceModule($id2);
        }

        // Przed błędami
        if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
            $additionalWarnings = $serviceModule->serviceAdminManagePre($_POST);
            $warnings = array_merge((array)$warnings, (array)$additionalWarnings);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        // Po błędach wywołujemy metodę modułu
        if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
            $moduleData = $serviceModule->serviceAdminManagePost($_POST);

            // Tworzymy elementy SET zapytania
            if (isset($moduleData['query_set'])) {
                $set = '';
                foreach ($moduleData['query_set'] as $element) {
                    if (strlen($set)) {
                        $set .= ", ";
                    }

                    $set .= $db->prepare("`%s` = '{$element['type']}'", [
                        $element['column'],
                        $element['value'],
                    ]);
                }
            }
        }

        if (isset($set) && strlen($set)) {
            $set = ", " . $set;
        }

        if ($action == "service_add") {
            $db->query(
                $db->prepare(
                    "INSERT INTO `" .
                    TABLE_PREFIX .
                    "services` " .
                    "SET `id`='%s', `name`='%s', `short_description`='%s', `description`='%s', `tag`='%s', " .
                    "`module`='%s', `groups`='%s', `order` = '%d' " .
                    "{$set}",
                    [
                        $id,
                        $name,
                        $shortDescription,
                        $description,
                        $tag,
                        $module,
                        implode(";", $groups),
                        trim($order),
                    ]
                )
            );

            log_info(
                $langShop->sprintf(
                    $langShop->translate('service_admin_add'),
                    $user->getUsername(),
                    $user->getUid(),
                    $id
                )
            );
            return new ApiResponse('ok', $lang->translate('service_added'), 1, [
                'length' => 10000,
            ]);
        }

        if ($action == "service_edit") {
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
                        $id,
                        $name,
                        $shortDescription,
                        $description,
                        $tag,
                        implode(";", $groups),
                        $order,
                        $id2,
                    ]
                )
            );

            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('service_admin_edit'),
                        $user->getUsername(),
                        $user->getUid(),
                        $id2
                    )
                );
                return new ApiResponse('ok', $lang->translate('service_edit'), 1);
            }
            return new ApiResponse("not_edited", $lang->translate('service_no_edit'), 0);
        }
    }
}