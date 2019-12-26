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

class ServiceCollection
{
    public function post(
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
        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $shortDescription = $request->request->get('short_description');
        $order = $request->request->get('order');
        $description = $request->request->get('description');
        $tag = $request->request->get('tag');
        $module = $request->request->get('module');
        $groups = $request->request->get('groups', []);

        $warnings = [];
        $set = "";

        if (($serviceModule = $heart->getEmptyServiceModule($module)) === null) {
            $warnings['module'][] = $lang->translate('wrong_module');
        }

        $serviceService->validateBody($request->request->all(), $warnings, $set, $serviceModule);

        if (strlen($id) > 16) {
            $warnings['id'][] = $lang->translate('long_service_id');
        }

        // Sprawdzanie czy usługa o takim ID już istnieje
        if ($heart->getService($id) !== null) {
            $warnings['id'][] = $lang->translate('id_exist');
        }

        try {
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
        } catch (SqlQueryException $e) {
            if ($e->getErrorno() === 1062) {
                return new ApiResponse("error", $lang->translate('create_price_duplication'), 0);
            }

            throw $e;
        }

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('service_admin_add'),
                $user->getUsername(),
                $user->getUid(),
                $id
            )
        );
        return new ApiResponse('ok', $lang->translate('service_added'), true, [
            'length' => 10000,
        ]);
    }
}
