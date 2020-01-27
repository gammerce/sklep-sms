<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\GroupRepository;
use App\Support\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupResource
{
    public function put(
        $groupId,
        Request $request,
        TranslationManager $translationManager,
        Database $db,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();

        $name = $request->request->get('name');

        $set = "";
        $result = $db->query("DESCRIBE ss_groups");
        foreach ($result as $row) {
            if (in_array($row['Field'], ["id", "name"])) {
                continue;
            }

            $set .= $db->prepare(", `%s`='%d'", [
                $row['Field'],
                $request->request->get($row['Field']),
            ]);
        }

        $statement = $db->query(
            $db->prepare("UPDATE `ss_groups` " . "SET `name` = '%s'{$set} " . "WHERE `id` = '%d'", [
                $name,
                $groupId,
            ])
        );

        if ($statement->rowCount()) {
            $databaseLogger->logWithActor('log_group_edited', $groupId);
            return new SuccessApiResponse($lang->t('group_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('group_no_edit'), 0);
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
            $databaseLogger->logWithActor('log_group_deleted', $groupId);
            return new SuccessApiResponse($lang->t('delete_group'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_group'), 0);
    }
}
