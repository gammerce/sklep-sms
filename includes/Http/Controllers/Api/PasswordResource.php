<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Loggers\DatabaseLogger;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PasswordResource
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Database $db,
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

        $salt = get_random_string(8);

        $db->query(
            $db->prepare("UPDATE `ss_users` " . "SET password='%s', salt='%s'" . "WHERE uid='%d'", [
                hash_password($pass, $salt),
                $salt,
                $user->getUid(),
            ])
        );

        $logger->logWithActor("log_password_changed");

        return new ApiResponse("password_changed", $lang->t('password_changed'), 1);
    }
}
