<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\CaptchaRule;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueSteamIdRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class RegisterController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        Auth $auth,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                "username" => trim($request->request->get("username")),
                "password" => $request->request->get("password"),
                "password_repeat" => $request->request->get("password_repeat"),
                "email" => trim($request->request->get("email")),
                "email_repeat" => trim($request->request->get("email_repeat")),
                "forename" => trim($request->request->get("forename")),
                "surname" => trim($request->request->get("surname")),
                "steam_id" => trim($request->request->get("steam_id")),
                "h-captcha-response" => $request->request->get("h-captcha-response"),
            ],
            [
                "username" => [new RequiredRule(), new UsernameRule(), new UniqueUsernameRule()],
                "password" => [new RequiredRule(), new ConfirmedRule(), new PasswordRule()],
                "email" => [
                    new RequiredRule(),
                    new ConfirmedRule(),
                    new EmailRule(),
                    new UniqueUserEmailRule(),
                ],
                "forename" => [],
                "surname" => [],
                "steam_id" => [new SteamIdRule(), new UniqueSteamIdRule()],
                "h-captcha-response" => [new RequiredRule(), new CaptchaRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $createdUser = $userRepository->create(
            $validated["username"],
            $validated["password"],
            $validated["email"],
            $validated["forename"],
            $validated["surname"],
            $validated["steam_id"],
            get_ip($request),
            "1",
            0
        );

        $auth->loginUser($request, $createdUser);

        $logger->log(
            "log_new_account",
            $createdUser->getId(),
            $createdUser->getUsername(),
            $createdUser->getRegIp()
        );

        return new ApiResponse("registered", $lang->t("register_success"), 1);
    }
}
