<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\AntiSpamQuestionRule;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueSteamIdRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\AntiSpamQuestionRepository;
use App\Repositories\UserRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class RegisterController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        AntiSpamQuestionRepository $antiSpamQuestionRepository,
        DatabaseLogger $logger
    ) {
        $session = $request->getSession();
        $lang = $translationManager->user();

        $antispamQuestionId = $request->request->get('as_id');

        // Get new antispam question
        $data = [];
        $antispamQuestion = $antiSpamQuestionRepository->findRandom();
        $data['antispam']['question'] = $antispamQuestion->getQuestion();
        $data['antispam']['id'] = $antispamQuestion->getId();

        // Is antispam question correct
        if (!$session->has("asid") || $antispamQuestionId != $session->get("asid")) {
            return new ApiResponse("wrong_sign", $lang->t('wrong_sign'), 0, $data);
        }

        // Let's store antispam question id in session
        $session->set("asid", $antispamQuestion->getId());

        $validator = new Validator(
            [
                "username" => trim($request->request->get('username')),
                "password" => $request->request->get('password'),
                "password_repeat" => $request->request->get('password_repeat'),
                "email" => trim($request->request->get('email')),
                "email_repeat" => trim($request->request->get('email_repeat')),
                "forename" => trim($request->request->get('forename')),
                "surname" => trim($request->request->get('surname')),
                "steam_id" => trim($request->request->get('steam_id')),
                "as_answer" => $request->request->get('as_answer'),
                "as_id" => $antispamQuestionId,
            ],
            [
                "username" => [new RequiredRule(), new UsernameRule(), new UniqueUsernameRule()],
                "password" => [new RequiredRule(), new ConfirmedRule(), new PasswordRule()],
                "email" => [
                    new RequiredRule(),
                    new ConfirmedRule(),
                    new EmailRule(),
                    new UniqueUserEmailRule(),
                ],
                "forename" => [],
                "surname" => [],
                "steam_id" => [new SteamIdRule(), new UniqueSteamIdRule()],
                "as_answer" => [new AntiSpamQuestionRule()],
            ]
        );

        $validated = $validator->validateOrFailWith($data);

        $createdUser = $userRepository->create(
            $validated['username'],
            $validated['password'],
            $validated['email'],
            $validated['forename'],
            $validated['surname'],
            $validated['steam_id'],
            get_ip($request),
            '1',
            0
        );

        $logger->log(
            'log_new_account',
            $createdUser->getUid(),
            $createdUser->getUsername(),
            $createdUser->getRegIp()
        );

        return new ApiResponse("registered", $lang->t('register_success'), 1, $data);
    }
}
