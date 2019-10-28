<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use App\TranslationManager;
use App\Validation\Rules\RequiredRule;
use App\Validation\Rules\SteamIdRule;
use App\Validation\Rules\UniqueUsernameRule;
use App\Validation\Validator;
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
                "username" => [$requiredRule, $uniqueUsernameRule],
                "steam_id" => [new SteamIdRule()],
            ]
        );

        $validator->validateOrFail();

        $user = $auth->user();
        $user->setUsername($username);
        $user->setForename($forename);
        $user->setSurname($surname);
        $user->setSteamId($steamId);

        $userRepository->update($user);

        return new ApiResponse('ok', $lang->translate('user_edit'), 1);
    }
}
