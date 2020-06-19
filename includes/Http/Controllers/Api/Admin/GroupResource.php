<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\GroupRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupResource
{
    public function put(
        $groupId,
        Request $request,
        GroupRepository $groupRepository,
        TranslationManager $translationManager,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();
        $name = $request->request->get("name");
        $permissions = $request->request->get("permissions");

        $updated = $groupRepository->update($groupId, $name, as_permission_list($permissions));

        if ($updated) {
            $databaseLogger->logWithActor("log_group_edited", $groupId);
            return new SuccessApiResponse($lang->t("group_edit"));
        }

        return new ApiResponse("not_edited", $lang->t("group_no_edit"), 0);
    }

    public function delete(
        $groupId,
        GroupRepository $groupRepository,
        TranslationManager $translationManager,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        $deleted = $groupRepository->delete($groupId);

        if ($deleted) {
            $databaseLogger->logWithActor("log_group_deleted", $groupId);
            return new SuccessApiResponse($lang->t("delete_group"));
        }

        return new ApiResponse("not_deleted", $lang->t("no_delete_group"), 0);
    }
}
