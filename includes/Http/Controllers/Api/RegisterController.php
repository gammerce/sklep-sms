<?php
namespace App\Http\Controllers\Api;

use App\System\Auth;
use App\System\Database;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;
use App\Http\Validation\Rules\AntispamQuestionRule;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;

class RegisterController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Database $db,
        UserRepository $userRepository,
        UniqueUsernameRule $uniqueUsernameRule,
        RequiredRule $requiredRule,
        ConfirmedRule $confirmedRule,
        UniqueUserEmailRule $uniqueUserEmailRule,
        AntispamQuestionRule $antispamQuestionRule
    ) {
        $session = $request->getSession();
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        $username = trim($request->request->get('username'));
        $password = $request->request->get('password');
        $passwordRepeat = $request->request->get('password_repeat');
        $email = trim($request->request->get('email'));
        $emailRepeat = trim($request->request->get('email_repeat'));
        $forename = trim($request->request->get('forename'));
        $surname = trim($request->request->get('surname'));
        $steamId = trim($request->request->get('steam_id'));
        $asId = $request->request->get('as_id');
        $asAnswer = $request->request->get('as_answer');

        // Get new antispam question
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

        $validator = new Validator(
            [
                "username" => $username,
                "password" => $password,
                "password_repeat" => $passwordRepeat,
                "email" => $email,
                "email_repeat" => $emailRepeat,
                "steam_id" => $steamId,
                "as_answer" => $asAnswer,
                "as_id" => $asId,
            ],
            [
                "username" => [$requiredRule, $uniqueUsernameRule],
                "password" => [$requiredRule, $confirmedRule, new PasswordRule()],
                "email" => [$requiredRule, $confirmedRule, new EmailRule(), $uniqueUserEmailRule],
                "steam_id" => [new SteamIdRule()],
                "as_answer" => [$antispamQuestionRule],
            ]
        );

        $warnings = $validator->validate();
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
            get_ip($request),
            '1',
            0
        );

        log_to_db(
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
