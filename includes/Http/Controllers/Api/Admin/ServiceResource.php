<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServiceService;
use App\Repositories\ServiceRepository;
use App\Services\Interfaces\IServiceAdminManage;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class ServiceResource
{
    public function put(
        $serviceId,
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Heart $heart,
        ServiceService $serviceService,
        ServiceRepository $serviceRepository
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $newId = $request->request->get('new_id');
        $name = $request->request->get('name');
        $shortDescription = $request->request->get('short_description');
        $order = $request->request->get('order');
        $description = $request->request->get('description');
        $tag = $request->request->get('tag');
        $groups = $request->request->get('groups', []);

        $warnings = [];
        $body = $request->request->all();
        // For backward compatibility. Some service modules use that field.
        $body["id"] = $serviceId;

        if ($serviceId !== $newId && $heart->getService($newId)) {
            $warnings['new_id'][] = $lang->translate('id_exist');
        }

        $serviceModule = $heart->getServiceModule($serviceId);
        $serviceService->validateBody($body, $warnings, $serviceModule);

        $additionalData =
            $serviceModule instanceof IServiceAdminManage
                ? $serviceModule->serviceAdminManagePost($body)
                : [];

        $updated = $serviceRepository->update(
            $serviceId,
            $newId,
            $name,
            $shortDescription,
            $description,
            $tag,
            $groups,
            $order,
            array_get($additionalData, "data", []),
            array_get($additionalData, "types", 0),
            array_get($additionalData, "flags", '')
        );

        if ($updated) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('service_admin_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceId
                )
            );
            return new SuccessApiResponse($lang->translate('service_edit'));
        }

        return new ApiResponse("not_edited", $lang->translate('service_no_edit'), 0);
    }

    public function delete(
        $serviceId,
        TranslationManager $translationManager,
        ServiceRepository $serviceRepository,
        Auth $auth,
        Heart $heart
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $serviceModule = $heart->getServiceModule($serviceId);
        if ($serviceModule !== null) {
            $serviceModule->serviceDelete($serviceId);
        }

        try {
            $deleted = $serviceRepository->delete($serviceId);
        } catch (PDOException $e) {
            // It is affiliated with something
            if (get_error_code($e) == 1451) {
                return new ApiResponse(
                    "error",
                    $lang->translate('delete_service_er_row_is_referenced'),
                    0
                );
            }

            throw $e;
        }

        if ($deleted) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('service_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceId
                )
            );
            return new SuccessApiResponse($lang->translate('delete_service'));
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_service'), 0);
    }
}
