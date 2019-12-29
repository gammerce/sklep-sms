<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Services\ServiceService;
use App\Repositories\ServiceRepository;
use App\Services\Interfaces\IServiceAdminManage;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCollection
{
    public function post(
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
            $warnings['module'][] = $lang->translate('wrong_module');
        }

        if (strlen($id) > 16) {
            $warnings['id'][] = $lang->translate('long_service_id');
        }

        if ($heart->getService($id) !== null) {
            $warnings['id'][] = $lang->translate('id_exist');
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
