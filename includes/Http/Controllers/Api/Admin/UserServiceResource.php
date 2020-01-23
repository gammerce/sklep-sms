<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserServiceRepository;
use App\Services\UserServiceService;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        UserServiceService $userServiceService
    ) {
        $lang = $translationManager->user();

        $userService = $userServiceService->findOne($userServiceId);
        if (!$userService) {
            return new ApiResponse("no_service", $lang->t('no_service'), 0);
        }

        $serviceModule = $heart->getServiceModule($userService->getServiceId());
        if (!$serviceModule) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        $returnData = $serviceModule->userServiceAdminEdit($request->request->all(), $userService);

        if ($returnData === false) {
            return new ApiResponse("missing_method", $lang->t('no_edit_method'), 0);
        }

        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }

    public function delete(
        $userServiceId,
        Heart $heart,
        TranslationManager $translationManager,
        DatabaseLogger $logger,
        UserServiceService $userServiceService,
        UserServiceRepository $userServiceRepository
    ) {
        $lang = $translationManager->user();

        $userService = $userServiceService->findOne($userServiceId);
        if (!$userService) {
            return new ApiResponse("no_service", $lang->t('no_service'), 0);
        }

        $serviceModule = $heart->getServiceModule($userService->getServiceId());
        if ($serviceModule && !$serviceModule->userServiceDelete($userService, 'admin')) {
            return new ApiResponse(
                "user_service_cannot_be_deleted",
                $lang->t('user_service_cannot_be_deleted'),
                0
            );
        }

        $deleted = $userServiceRepository->delete($userService->getId());

        if ($serviceModule) {
            $serviceModule->userServiceDeletePost($userService);
        }

        if ($deleted) {
            $logger->logWithActor('log_user_service_deleted', $userService->getId());
            return new SuccessApiResponse($lang->t('delete_service'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_service'), 0);
    }
}
