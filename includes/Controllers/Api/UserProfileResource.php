<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Database;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserProfileResource
{
    public function put(Request $request, Database $db, TranslationManager $translationManager, UserRepository $userRepository, Auth $auth)
    {
        $lang = $translationManager->user();

        $username = trim($request->request->get('username'));
        $forename = trim($request->request->get('forename'));
        $surname = trim($request->request->get('surname'));
        $steamId = trim($request->request->get('steam_id'));

        $warnings = [];

        if ($warning = check_for_warnings("username", $username)) {
            $warnings['username'] = array_merge((array)$warnings['username'], $warning);
        }

        $result = $db->query(
            $db->prepare(
                "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " . "WHERE `username` = '%s'",
                [$username]
            )
        );
        if ($db->numRows($result)) {
            $warnings['username'][] = $lang->translate('nick_occupied');
        }

        if ($warning = check_for_warnings("sid", $steamId)) {
            $warnings['steam_id'] = array_merge((array)$warnings['steam_id'], $warning);
        }

        if (!empty($warnings)) {
            $data['warnings'] = format_warnings($warnings);
            return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
        }

        $user = $auth->user();
        $user->setUsername($username);
        $user->setForename($forename);
        $user->setSurname($surname);
        // TODO Update steamId

        $userRepository->update($user);

        return new ApiResponse('ok', $lang->translate('user_edit'), 1);
    }
}
