<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Support\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupCollection
{
    public function post(
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

        $db->query($db->prepare("INSERT INTO `ss_groups` SET `name` = '%s'{$set}", [$name]));
        $groupId = $db->lastId();

        $databaseLogger->logWithActor('log_group_added', $groupId);

        return new SuccessApiResponse($lang->t('group_add'), [
            "data" => [
                "id" => $groupId,
            ],
        ]);
    }
}
