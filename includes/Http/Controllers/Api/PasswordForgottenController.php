<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\Routing\UrlGenerator;
use App\Support\Mailer;
use App\Support\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PasswordForgottenController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        UrlGenerator $url,
        Template $template,
        Mailer $mailer,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $warnings = [];

        $username = trim($request->request->get('username'));
        $email = trim($request->request->get('email'));

        if ($username || (!$username && !$email)) {
            if ($warning = check_for_warnings("username", $username)) {
                $warnings['username'] = array_merge((array) $warnings['username'], $warning);
            }
            if (strlen($username)) {
                $editedUser = $userRepository->findByUsername($username);

                if (!$editedUser) {
                    $warnings['username'][] = $lang->t('nick_no_account');
                }
            }
        }

        if (!strlen($username)) {
            if ($warning = check_for_warnings("email", $email)) {
                $warnings['email'] = array_merge((array) $warnings['email'], $warning);
            }
            if (strlen($email)) {
                $editedUser = $userRepository->findByEmail($email);

                if (!$editedUser) {
                    $warnings['email'][] = $lang->t('email_no_account');
                }
            }
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $code = $userRepository->createResetPasswordKey($editedUser->getUid());

        $link = $url->to("/page/reset_password?code=" . urlencode($code));
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
            $logger->log(
                'reset_key_email',
                $editedUser->getUsername(),
                $editedUser->getUid(),
                $editedUser->getEmail(),
                $username,
                $email
            );
            $data['username'] = $editedUser->getUsername();
            return new ApiResponse("sent", $lang->t('email_sent'), 1, $data);
        }

        throw new UnexpectedValueException("Invalid ret value");
    }
}
