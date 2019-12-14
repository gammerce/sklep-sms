<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Validator;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserProfileResource
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        Auth $auth,
        UniqueUsernameRule $uniqueUsernameRule,
        RequiredRule $requiredRule
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $username = trim($request->request->get('username'));
        $forename = trim($request->request->get('forename'));
        $surname = trim($request->request->get('surname'));
        $steamId = trim($request->request->get('steam_id'));

        $validator = new Validator(
            [
                "username" => $username,
                "steam_id" => $steamId,
            ],
            [
                "username" => [$requiredRule, $uniqueUsernameRule->setUserId($user->getUid())],
                "steam_id" => [new SteamIdRule()],
            ]
        );

        $validator->validateOrFail();

        $user->setUsername($username);
        $user->setForename($forename);
        $user->setSurname($surname);
        $user->setSteamId($steamId);

        $userRepository->update($user);

        return new ApiResponse('ok', $lang->translate('profile_edit'), 1);
    }
}
