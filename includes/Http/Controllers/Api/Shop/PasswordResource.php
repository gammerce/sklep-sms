<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserPasswordRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PasswordResource
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        UserRepository $userRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $validator = new Validator($request->request->all(), [
            'old_pass' => [new RequiredRule(), new UserPasswordRule($user)],
            'pass' => [new RequiredRule(), new PasswordRule(), new ConfirmedRule()],
        ]);

        $validated = $validator->validateOrFail();

        $userRepository->updatePassword($user->getUid(), $validated['pass']);
        $logger->logWithActor("log_password_changed");

        return new ApiResponse("password_changed", $lang->t('password_changed'), 1);
    }
}
