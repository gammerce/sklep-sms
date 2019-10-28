<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class RegisterController
{
    public function post(
        Request $request,
        Auth $auth,
        TranslationManager $translationManager,
        Database $db,
        UserRepository $userRepository
    ) {
        $session = $request->getSession();
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        if ($auth->check()) {
            return new ApiResponse("logged_in", $lang->translate('logged'), 0);
        }

        $username = trim($request->request->get('username'));
        $password = $request->request->get('password');
        $passwordr = $request->request->get('password_repeat');
        $email = trim($request->request->get('email'));
        $emailr = trim($request->request->get('email_repeat'));
        $forename = trim($request->request->get('forename'));
        $surname = trim($request->request->get('surname'));
        $steamId = trim($request->request->get('steam_id'));
        $asId = $request->request->get('as_id');
        $asAnswer = $request->request->get('as_answer');

        $warnings = [];
        $data = [];

        $antispamQuestion = $db->fetchArrayAssoc(
            $db->query(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "antispam_questions` " .
                    "ORDER BY RAND() " .
                    "LIMIT 1"
            )
        );
        $data['antispam']['question'] = $antispamQuestion['question'];
        $data['antispam']['id'] = $antispamQuestion['id'];

        // Is antispam question correct
        if (!$session->has("asid") || $asId != $session->get("asid")) {
            return new ApiResponse("wrong_sign", $lang->translate('wrong_sign'), 0, $data);
        }

        // Let's store antispam question id in session
        $session->set("asid", $antispamQuestion['id']);

        if ($warning = check_for_warnings("username", $username)) {
            $warnings['username'] = array_merge((array) $warnings['username'], $warning);
        }

        $result = $db->query(
            $db->prepare(
                "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " . "WHERE `username` = '%s'",
                [$username]
            )
        );
        if ($db->numRows($result)) {
            $warnings['username'][] = $lang->translate('nick_occupied');
        }

        if ($steamId && ($warning = check_for_warnings("sid", $steamId))) {
            $warnings['steam_id'] = array_merge((array) $warnings['steam_id'], $warning);
        }

        // Password
        if ($warning = check_for_warnings("password", $password)) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }
        if ($password != $passwordr) {
            $warnings['password_repeat'][] = $lang->translate('different_pass');
        }

        if ($warning = check_for_warnings("email", $email)) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Email
        $result = $db->query(
            $db->prepare(
                "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " . "WHERE `email` = '%s'",
                [$email]
            )
        );
        if ($db->numRows($result)) {
            $warnings['email'][] = $lang->translate('email_occupied');
        }

        if ($email != $emailr) {
            $warnings['email_repeat'][] = $lang->translate('different_email');
        }

        $result = $db->query(
            $db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " . "WHERE `id` = '%d'",
                [$asId]
            )
        );
        $antispamQuestion = $db->fetchArrayAssoc($result);
        if (!in_array(strtolower($asAnswer), explode(";", $antispamQuestion['answers']))) {
            $warnings['as_answer'][] = $lang->translate('wrong_anti_answer');
        }

        if ($warnings) {
            throw new ValidationException($warnings, $data);
        }

        $createdUser = $userRepository->create(
            $username,
            $password,
            $email,
            $forename,
            $surname,
            $steamId,
            get_ip($request)
        );

        log_info(
            $langShop->sprintf(
                $langShop->translate('new_account'),
                $createdUser->getUid(),
                $createdUser->getUsername(false),
                $createdUser->getRegIp()
            )
        );

        return new ApiResponse("registered", $lang->translate('register_success'), 1, $data);
    }
}
