<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupResource
{
    public function put(
        $groupId,
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Database $db
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $name = $request->request->get('name');

        $set = "";
        $result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
        while ($row = $db->fetchArrayAssoc($result)) {
            if (in_array($row['Field'], ["id", "name"])) {
                continue;
            }

            $set .= $db->prepare(", `%s`='%d'", [
                $row['Field'],
                $request->request->get($row['Field']),
            ]);
        }

        $statement = $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "groups` " .
                    "SET `name` = '%s'{$set} " .
                    "WHERE `id` = '%d'",
                [$name, $groupId]
            )
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('group_admin_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $groupId
                )
            );
            return new ApiResponse('ok', $lang->translate('group_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('group_no_edit'), 0);
    }

    public function delete(
        $groupId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "groups` WHERE `id` = '%d'", [$groupId])
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->sprintf(
                    $langShop->translate('group_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $groupId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_group'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_group'), 0);
    }
}
