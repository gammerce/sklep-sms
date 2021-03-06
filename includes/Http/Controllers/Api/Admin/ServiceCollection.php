<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServiceService;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServiceModuleExistsRule;
use App\Http\Validation\Rules\ServiceNotExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Repositories\ServiceRepository;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        ServiceService $serviceService,
        ServiceRepository $serviceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $module = $request->request->get("module");
        $serviceModule = $serviceModuleManager->getEmpty($module);

        $validator = new Validator($request->request->all(), [
            "id" => [new RequiredRule(), new MaxLengthRule(16), new ServiceNotExistsRule()],
            "module" => [new RequiredRule(), new ServiceModuleExistsRule()],
        ]);
        $validator = $serviceService->extendValidator($validator, $serviceModule);
        $validated = $validator->validateOrFail();

        $additionalData =
            $serviceModule instanceof IServiceAdminManage
                ? $serviceModule->serviceAdminManagePost($validated)
                : [];

        $service = $serviceRepository->create(
            $validated["id"],
            $validated["name"],
            $validated["short_description"],
            $validated["description"],
            $validated["tag"],
            $serviceModule->getModuleId(),
            $validated["groups"],
            (int) $validated["order"],
            array_get($additionalData, "data", []),
            array_get($additionalData, "types", 0),
            array_get($additionalData, "flags", "")
        );

        if ($serviceModule instanceof IServicePurchaseExternal) {
            $serviceService->updateServiceServerLinks(
                $service->getId(),
                $validated["server_ids"] ?: []
            );
        }

        $logger->logWithActor("log_service_added", $service->getId());
        return new SuccessApiResponse($lang->t("service_added"), [
            "length" => 10000,
        ]);
    }
}
