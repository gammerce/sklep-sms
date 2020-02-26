<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PasswordResetController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                'code' => $request->request->get('code'),
                'pass' => $request->request->get('pass'),
                'pass_repeat' => $request->request->get('pass_repeat'),
            ],
            [
                'code' => [new RequiredRule()],
                'pass' => [new RequiredRule(), new PasswordRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $resetKey = $validated['code'];
        $pass = $validated['pass'];

        $user = $userRepository->findByResetKey($resetKey);

        if (!$user) {
            return new ApiResponse("wrong_sign", $lang->t('wrong_sign'), 0);
        }

        $userRepository->updatePassword($user->getUid(), $pass);
        $logger->log('reset_pass', $user->getUid());

        return new ApiResponse("password_changed", $lang->t('password_changed'), 1);
    }
}
