<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
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

        $warnings = [];

        $oldpass = $request->request->get('old_pass');
        $pass = $request->request->get('pass');
        $passr = $request->request->get('pass_repeat');

        if ($warning = check_for_warnings("password", $pass)) {
            $warnings['pass'] = array_merge((array) $warnings['pass'], $warning);
        }
        if ($pass != $passr) {
            $warnings['pass_repeat'][] = $lang->t('different_values');
        }

        if (hash_password($oldpass, $user->getSalt()) != $user->getPassword()) {
            $warnings['old_pass'][] = $lang->t('old_pass_wrong');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $userRepository->updatePassword($user->getUid(), $pass);
        $logger->logWithActor("log_password_changed");

        return new ApiResponse("password_changed", $lang->t('password_changed'), 1);
    }
}
