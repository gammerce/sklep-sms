<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Repositories\ServiceCodeRepository;
use App\System\Auth;
use App\Translation\TranslationManager;

class ServiceCodeResource
{
    public function delete(
        $serviceCodeId,
        TranslationManager $translationManager,
        ServiceCodeRepository $serviceCodeRepository,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $deleted = $serviceCodeRepository->delete($serviceCodeId);

        if ($deleted) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('code_deleted_admin'),
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceCodeId
                )
            );
            return new ApiResponse('ok', $lang->translate('code_deleted'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('code_not_deleted'), 0);
    }
}
