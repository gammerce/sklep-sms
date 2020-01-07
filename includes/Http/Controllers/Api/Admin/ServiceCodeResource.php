<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
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
                $langShop->t(
                    'code_deleted_admin',
                    $user->getUsername(),
                    $user->getUid(),
                    $serviceCodeId
                )
            );
            return new SuccessApiResponse($lang->t('code_deleted'));
        }

        return new ApiResponse("not_deleted", $lang->t('code_not_deleted'), 0);
    }
}
