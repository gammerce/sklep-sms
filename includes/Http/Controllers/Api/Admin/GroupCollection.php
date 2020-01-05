<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class GroupCollection
{
    public function post(
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

        $db->query(
            $db->prepare("INSERT INTO `" . TABLE_PREFIX . "groups` " . "SET `name` = '%s'{$set}", [
                $name,
            ])
        );

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('group_admin_add'),
                $user->getUsername(),
                $user->getUid(),
                $db->lastId()
            )
        );
        return new SuccessApiResponse($lang->t('group_add'));
    }
}
