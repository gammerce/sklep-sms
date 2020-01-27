<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PasswordResetController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        UserRepository $userRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                'uid' => as_int($request->request->get('uid')),
                'sign' => $request->request->get('sign'),
                'pass' => $request->request->get('pass'),
                'pass_repeat' => $request->request->get('pass_repeat'),
            ],
            [
                'uid' => [],
                'sign' => [new RequiredRule()],
                'pass' => [new RequiredRule(), new PasswordRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $uid = $validated['uid'];
        $sign = $validated['sign'];
        $pass = $validated['pass'];

        if ($sign !== md5($uid . $settings->getSecret())) {
            return new ApiResponse("wrong_sign", $lang->t('wrong_sign'), 0);
        }

        $userRepository->updatePassword($uid, $pass);
        $logger->log('reset_pass', $uid);

        return new ApiResponse("password_changed", $lang->t('password_changed'), 1);
    }
}
