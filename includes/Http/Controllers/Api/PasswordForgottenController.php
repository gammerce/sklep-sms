<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Routes\UrlGenerator;
use App\System\Database;
use App\System\Heart;
use App\System\Mailer;
use App\System\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PasswordForgottenController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Database $db,
        Heart $heart,
        UrlGenerator $url,
        Template $template,
        Mailer $mailer
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        $warnings = [];

        $username = trim($request->request->get('username'));
        $email = trim($request->request->get('email'));

        if ($username || (!$username && !$email)) {
            if ($warning = check_for_warnings("username", $username)) {
                $warnings['username'] = array_merge((array) $warnings['username'], $warning);
            }
            if (strlen($username)) {
                $result = $db->query(
                    $db->prepare(
                        "SELECT `uid` FROM `" .
                            TABLE_PREFIX .
                            "users` " .
                            "WHERE `username` = '%s'",
                        [$username]
                    )
                );

                if (!$result->rowCount()) {
                    $warnings['username'][] = $lang->t('nick_no_account');
                } else {
                    $row = $result->fetch();
                }
            }
        }

        if (!strlen($username)) {
            if ($warning = check_for_warnings("email", $email)) {
                $warnings['email'] = array_merge((array) $warnings['email'], $warning);
            }
            if (strlen($email)) {
                $result = $db->query(
                    $db->prepare(
                        "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " . "WHERE `email` = '%s'",
                        [$email]
                    )
                );

                if (!$result->rowCount()) {
                    $warnings['email'][] = $lang->t('email_no_account');
                } else {
                    $row = $result->fetch();
                }
            }
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        // Pobranie danych użytkownika
        $editedUser = $heart->getUser($row['uid']);

        $key = get_random_string(32);
        $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `reset_password_key`='%s' " .
                    "WHERE `uid`='%d'",
                [$key, $editedUser->getUid()]
            )
        );

        $link = $url->to("/page/reset_password?code=" . urlencode($key));
        $text = $template->render("emails/forgotten_password", compact('editedUser', 'link'));
        $ret = $mailer->send(
            $editedUser->getEmail(),
            $editedUser->getUsername(),
            "Reset Hasła",
            $text
        );

        if ($ret == "not_sent") {
            return new ApiResponse("not_sent", $lang->t('keyreset_error'), 0);
        }

        if ($ret == "wrong_email") {
            return new ApiResponse("wrong_sender_email", $lang->t('wrong_email'), 0);
        }

        if ($ret == "sent") {
            log_to_db(
                $langShop->t(
                    'reset_key_email',
                    $editedUser->getUsername(),
                    $editedUser->getUid(),
                    $editedUser->getEmail(),
                    $username,
                    $email
                )
            );
            $data['username'] = $editedUser->getUsername();
            return new ApiResponse("sent", $lang->t('email_sent'), 1, $data);
        }

        throw new UnexpectedValueException("Invalid ret value");
    }
}
