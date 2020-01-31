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
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
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
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();
        $editedUser = $heart->getUser($userId);

        $validator = new Validator($request->request->all(), [
            "username" => [
                new RequiredRule(),
                new UsernameRule(),
                new UniqueUsernameRule($editedUser->getUid()),
            ],
            "email" => [new RequiredRule(), new UniqueUserEmailRule($editedUser->getUid())],
            "steam_id" => [new SteamIdRule()],
            "wallet" => [new NumberRule()],
            "groups" => [new UserGroupsRule()],
        ]);

        $validated = $validator->validateOrFail();

        $editedUser->setUsername($validated['username']);
        $editedUser->setForename($validated['forename']);
        $editedUser->setSurname($validated['surname']);
        $editedUser->setEmail($validated['email']);
        $editedUser->setSteamId($validated['steam_id']);
        $editedUser->setGroups($validated['groups']);
        $editedUser->setWallet(ceil($validated['wallet'] * 100));

        $userRepository->update($editedUser);

        $logger->logWithActor('log_user_edited', $userId);

        return new SuccessApiResponse($lang->t('user_edit'));
    }

    public function delete(
        $userId,
        UserRepository $userRepository,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $deleted = $userRepository->delete($userId);

        if ($deleted) {
            $logger->logWithActor('log_user_deleted', $userId);
            return new SuccessApiResponse($lang->t('delete_user'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_user'), 0);
    }
}
