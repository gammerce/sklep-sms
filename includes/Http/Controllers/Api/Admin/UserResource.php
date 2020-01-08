<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UserGroupsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserResource
{
    public function put(
        $userId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        UserRepository $userRepository,
        RequiredRule $requiredRule,
        UniqueUsernameRule $uniqueUsernameRule,
        UniqueUserEmailRule $uniqueUserEmailRule,
        UserGroupsRule $userGroupsRule,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

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
            "email" => [$requiredRule, $uniqueUserEmailRule->setUserId($editedUser->getUid())],
            "steam_id" => [new SteamIdRule()],
            "wallet" => [new NumberRule()],
            "groups" => [$userGroupsRule],
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

        $logger->logWithActor('log_user_edited', $userId);

        return new SuccessApiResponse($lang->t('user_edit'));
    }

    public function delete(
        $userId,
        Database $db,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "users` " . "WHERE `uid` = '%d'", [
                $userId,
            ])
        );

        if ($statement->rowCount()) {
            $logger->logWithActor('log_user_deleted', $userId);
            return new SuccessApiResponse($lang->t('delete_user'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_user'), 0);
    }
}
