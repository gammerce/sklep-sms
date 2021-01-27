<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServiceService;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServiceNotExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Repositories\ServiceRepository;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class ServiceResource
{
    public function put(
        $serviceId,
        Request $request,
        TranslationManager $translationManager,
        ServiceService $serviceService,
        ServiceModuleManager $serviceModuleManager,
        ServiceRepository $serviceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            array_merge($request->request->all(), [
                // For backward compatibility. Some service modules use that field.
                "id" => $serviceId,
            ]),
            [
                "id" => [],
                "new_id" => [new RequiredRule(), new ServiceNotExistsRule($serviceId)],
            ]
        );

        $serviceModule = $serviceModuleManager->get($serviceId);
        $serviceService->extendValidator($validator, $serviceModule);

        $validated = $validator->validateOrFail();

        $additionalData =
            $serviceModule instanceof IServiceAdminManage
                ? $serviceModule->serviceAdminManagePost($validated)
                : [];

        $serviceRepository->update(
            $serviceId,
            $validated["new_id"],
            $validated["name"],
            $validated["short_description"],
            $validated["description"],
            $validated["tag"],
            $validated["groups"],
            $validated["order"],
            array_get($additionalData, "data", []),
            array_get($additionalData, "types", 0),
            array_get($additionalData, "flags", "")
        );
        $serviceService->updateServiceServerLinks(
            $validated["new_id"],
            $validated["server_ids"] ?: []
        );
        $logger->logWithActor("log_service_edited", $validated["new_id"]);

        return new SuccessApiResponse($lang->t("service_edit"));
    }

    public function delete(
        $serviceId,
        TranslationManager $translationManager,
        ServiceRepository $serviceRepository,
        ServiceModuleManager $serviceModuleManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $serviceModule = $serviceModuleManager->get($serviceId);
        if ($serviceModule !== null) {
            $serviceModule->serviceDelete($serviceId);
        }

        try {
            $deleted = $serviceRepository->delete($serviceId);
        } catch (PDOException $e) {
            // It is affiliated with something
            if (get_error_code($e) == 1451) {
                return new ApiResponse("error", $lang->t("delete_service_er_row_is_referenced"), 0);
            }

            throw $e;
        }

        if ($deleted) {
            $logger->logWithActor("log_service_deleted", $serviceId);
            return new SuccessApiResponse($lang->t("delete_service"));
        }

        return new ApiResponse("not_deleted", $lang->t("no_delete_service"), 0);
    }
}
