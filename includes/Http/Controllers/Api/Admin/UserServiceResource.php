<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\Services\UserServiceService;
use App\Translation\TranslationManager;
use App\Managers\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        UserServiceService $userServiceService
    ) {
        $lang = $translationManager->user();

        $userService = $userServiceService->findOne($userServiceId);
        if (!$userService) {
            throw new EntityNotFoundException();
        }

        $serviceId = $request->request->get('service_id', $userService->getServiceId());

        $serviceModule = $serviceModuleManager->get($serviceId);
        if (!($serviceModule instanceof IServiceUserServiceAdminEdit)) {
            throw new InvalidServiceModuleException();
        }

        $result = $serviceModule->userServiceAdminEdit($request->request->all(), $userService);
        if (!$result) {
            return new ApiResponse('not_edited', $lang->t('not_edited_user_service'), false);
        }

        return new ApiResponse('ok', $lang->t('edited_user_service'), true);
    }

    public function delete(
        $userServiceId,
        ServiceModuleManager $serviceModuleManager,
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

        $serviceModule = $serviceModuleManager->get($userService->getServiceId());
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
