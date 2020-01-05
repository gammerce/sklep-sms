<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\System\Auth;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserServiceResource
{
    public function put(
        $userServiceId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart
    ) {
        $lang = $translationManager->user();

        $userService = get_users_services($userServiceId);

        // Brak takiej usługi w bazie
        if (empty($userService)) {
            return new ApiResponse("no_service", $lang->t('no_service'), 0);
        }

        $serviceModule = $heart->getServiceModule($userService['service']);
        if ($serviceModule === null) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        // Wykonujemy metode edycji usługi użytkownika przez admina na odpowiednim module
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
        Database $db,
        Heart $heart,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $userService = get_users_services($userServiceId);

        // Brak takiej usługi
        if (empty($userService)) {
            return new ApiResponse("no_service", $lang->t('no_service'), 0);
        }

        // Wywolujemy akcje przy usuwaniu
        if (
            ($serviceModule = $heart->getServiceModule($userService['service'])) !== null &&
            !$serviceModule->userServiceDelete($userService, 'admin')
        ) {
            return new ApiResponse(
                "user_service_cannot_be_deleted",
                $lang->t('user_service_cannot_be_deleted'),
                0
            );
        }

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "user_service` " . "WHERE `id` = '%d'", [
                $userService['id'],
            ])
        );

        if ($serviceModule !== null) {
            $serviceModule->userServiceDeletePost($userService);
        }

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->t(
                    'user_service_admin_delete',
                    $user->getUsername(),
                    $user->getUid(),
                    $userService['id']
                )
            );

            return new SuccessApiResponse($lang->t('delete_service'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_service'), 0);
    }
}
