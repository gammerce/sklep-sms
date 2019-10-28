<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PasswordResource
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        Database $db
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
            $warnings['pass_repeat'][] = $lang->translate('different_values');
        }

        if (hash_password($oldpass, $user->getSalt()) != $user->getPassword()) {
            $warnings['old_pass'][] = $lang->translate('old_pass_wrong');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $salt = get_random_string(8);

        $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET password='%s', salt='%s'" .
                    "WHERE uid='%d'",
                [hash_password($pass, $salt), $salt, $user->getUid()]
            )
        );

        log_info("Zmieniono hasło. ID użytkownika: {$user->getUid()}.");

        return new ApiResponse("password_changed", $lang->translate('password_changed'), 1);
    }
}
