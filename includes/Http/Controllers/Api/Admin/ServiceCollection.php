<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\ServiceService;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServiceRepository;
use App\Services\Interfaces\IServiceAdminManage;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        ServiceService $serviceService,
        ServiceRepository $serviceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $shortDescription = $request->request->get('short_description');
        $order = trim($request->request->get('order'));
        $description = $request->request->get('description');
        $tag = $request->request->get('tag');
        $module = $request->request->get('module');
        $groups = $request->request->get('groups', []);

        $warnings = [];

        if (($serviceModule = $heart->getEmptyServiceModule($module)) === null) {
            $warnings['module'][] = $lang->t('wrong_module');
        }

        if (!strlen($id)) {
            $warnings['id'][] = $lang->t('no_service_id');
        }

        if (strlen($id) > 16) {
            $warnings['id'][] = $lang->t('long_service_id');
        }

        if ($heart->getService($id) !== null) {
            $warnings['id'][] = $lang->t('id_exist');
        }

        $serviceService->validateBody($request->request->all(), $warnings, $serviceModule);

        $additionalData =
            $serviceModule instanceof IServiceAdminManage
                ? $serviceModule->serviceAdminManagePost($request->request->all())
                : [];

        $serviceRepository->create(
            $id,
            $name,
            $shortDescription,
            $description,
            $tag,
            $module,
            $groups,
            $order,
            array_get($additionalData, "data", []),
            array_get($additionalData, "types", 0),
            array_get($additionalData, "flags", '')
        );

        $logger->logWithActor('log_service_added', $id);

        return new SuccessApiResponse($lang->t('service_added'), [
            'length' => 10000,
        ]);
    }
}
