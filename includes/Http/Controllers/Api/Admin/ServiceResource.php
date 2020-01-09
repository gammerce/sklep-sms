<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServiceService;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServiceRepository;
use App\ServiceModules\Interfaces\IServiceAdminManage;
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
        Heart $heart,
        ServiceService $serviceService,
        ServiceRepository $serviceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

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
            $warnings['new_id'][] = $lang->t('id_exist');
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
            $logger->logWithActor('log_service_edited', $serviceId);
            return new SuccessApiResponse($lang->t('service_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('service_no_edit'), 0);
    }

    public function delete(
        $serviceId,
        TranslationManager $translationManager,
        ServiceRepository $serviceRepository,
        Heart $heart,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $serviceModule = $heart->getServiceModule($serviceId);
        if ($serviceModule !== null) {
            $serviceModule->serviceDelete($serviceId);
        }

        try {
            $deleted = $serviceRepository->delete($serviceId);
        } catch (PDOException $e) {
            // It is affiliated with something
            if (get_error_code($e) == 1451) {
                return new ApiResponse("error", $lang->t('delete_service_er_row_is_referenced'), 0);
            }

            throw $e;
        }

        if ($deleted) {
            $logger->logWithActor('log_service_deleted', $serviceId);
            return new SuccessApiResponse($lang->t('delete_service'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_service'), 0);
    }
}
