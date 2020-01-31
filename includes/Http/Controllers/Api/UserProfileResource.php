<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueSteamIdRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UsernameRule;
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
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $validator = new Validator(
            [
                "username" => trim($request->request->get('username')),
                "forename" => trim($request->request->get('forename')),
                "surname" => trim($request->request->get('surname')),
                "steam_id" => trim($request->request->get('steam_id')),
            ],
            [
                "username" => [
                    new RequiredRule(),
                    new UsernameRule(),
                    new UniqueUsernameRule($user->getUid()),
                ],
                "forename" => [],
                "surname" => [],
                "steam_id" => [new SteamIdRule(), new UniqueSteamIdRule($user->getUid())],
            ]
        );

        $validated = $validator->validateOrFail();

        $user->setUsername($validated['username']);
        $user->setForename($validated['forename']);
        $user->setSurname($validated['surname']);
        $user->setSteamId($validated['steam_id']);

        $userRepository->update($user);

        return new SuccessApiResponse($lang->t('profile_edit'));
    }
}
