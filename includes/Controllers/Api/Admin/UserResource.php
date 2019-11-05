<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Heart;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use App\TranslationManager;
use App\Validation\Rules\NumberRule;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\SteamIdRule;
use App\Validation\Rules\UniqueUserEmailRule;
use App\Validation\Rules\UniqueUsernameRule;
use App\Validation\Rules\UserGroupsRule;
use App\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;

class UserResource
{
    public function put(
        $userId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        UserRepository $userRepository,
        Auth $auth,
        RequiredRule $requiredRule,
        UniqueUsernameRule $uniqueUsernameRule,
        UniqueUserEmailRule $uniqueUserEmailRule,
        UserGroupsRule $userGroupsRule
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $editedUser = $heart->getUser($userId);

        $username = $request->request->get('username');
        $forename = $request->request->get('forename');
        $surname = $request->request->get('surname');
        $email = $request->request->get('email');
        $steamId = $request->request->get('steam_id');
        $groups = $request->request->get('groups');
        $wallet = $request->request->get('wallet');

        $validator = new Validator($request->request->all(), [
            "username" => [$requiredRule, $uniqueUsernameRule->setUserId($editedUser->getUid())],
            "email"    => [$requiredRule, $uniqueUserEmailRule->setUserId($editedUser->getUid())],
            "steam_id" => [new SteamIdRule()],
            "wallet"   => [new NumberRule()],
            "groups"   => [$userGroupsRule],
        ]);

        $validator->validateOrFail();

        $editedUser->setUsername($username);
        $editedUser->setForename($forename);
        $editedUser->setSurname($surname);
        $editedUser->setEmail($email);
        $editedUser->setSteamId($steamId);
        $editedUser->setGroups($groups);
        $editedUser->setWallet(ceil($wallet * 100));

        $userRepository->update($editedUser);

        log_info(
            $langShop->sprintf(
                $langShop->translate('user_admin_edit'),
                $user->getUsername(),
                $user->getUid(),
                $userId
            )
        );

        return new ApiResponse('ok', $lang->translate('user_edit'), 1);
    }

    public function destroy($userId, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $db->query(
            $db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "users` " . "WHERE `uid` = '%d'",
                [$userId]
            )
        );

        // Zwróć info o prawidłowym lub błędnym usunięciu
        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('user_admin_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $userId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_user'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_user'), 0);
    }
}
