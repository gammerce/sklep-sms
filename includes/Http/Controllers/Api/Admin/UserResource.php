<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\ArrayRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueSteamIdRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UserGroupsRule;
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\UserManager;
use App\Repositories\UserRepository;
use App\Support\Money;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\User\PermissionService;
use Symfony\Component\HttpFoundation\Request;

class UserResource
{
    public function put(
        $userId,
        Request $request,
        TranslationManager $translationManager,
        UserManager $userManager,
        UserRepository $userRepository,
        PermissionService $permissionService,
        Auth $auth,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();
        $editedUser = $userManager->get($userId);

        $validator = new Validator($request->request->all(), [
            "email" => [new RequiredRule(), new UniqueUserEmailRule($editedUser->getId())],
            "forename" => [],
            "groups" => [new ArrayRule(), new UserGroupsRule()],
            "steam_id" => [new SteamIdRule(), new UniqueSteamIdRule($editedUser->getId())],
            "surname" => [],
            "username" => [
                new RequiredRule(),
                new UsernameRule(),
                new UniqueUsernameRule($editedUser->getId()),
            ],
            "wallet" => [new RequiredRule(), new NumberRule()],
        ]);

        $validated = $validator->validateOrFail();

        $steamIdChanged =
            to_canonical_steam_id($editedUser->getSteamId()) !==
            to_canonical_steam_id($validated["steam_id"]);

        $editedUser->setEmail($validated["email"]);
        $editedUser->setForename($validated["forename"]);
        $editedUser->setSteamId(to_legacy_steam_id($validated["steam_id"]));
        $editedUser->setSurname($validated["surname"]);
        $editedUser->setUsername($validated["username"]);
        $editedUser->setWallet(Money::fromPrice($validated["wallet"]));

        if (
            $validated["groups"] !== null &&
            $permissionService->canChangeUserGroup($auth->user(), $editedUser)
        ) {
            $editedUser->setGroups($validated["groups"]);
        }

        if (is_demo_user($editedUser->getId())) {
            return new ApiResponse("demo_mode", $lang->t("demo_mode"), false);
        }

        $userRepository->update($editedUser);

        // An admin replacing the Steam ID manually invalidates the verification
        // (only Steam OpenID may prove ownership).
        if ($steamIdChanged && $editedUser->isSteamVerified()) {
            $userRepository->clearSteamVerification($editedUser->getId());
            $editedUser->setSteamVerified(false);
        }

        $logger->logWithActor("log_user_edited", $userId);

        return new SuccessApiResponse($lang->t("user_edit"));
    }

    public function delete(
        $userId,
        UserRepository $userRepository,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        if (is_demo_user($userId)) {
            return new ApiResponse("demo_mode", $lang->t("demo_mode"), false);
        }

        $deleted = $userRepository->delete($userId);

        if ($deleted) {
            $logger->logWithActor("log_user_deleted", $userId);
            return new SuccessApiResponse($lang->t("delete_user"));
        }

        return new ApiResponse("not_deleted", $lang->t("no_delete_user"), 0);
    }
}
