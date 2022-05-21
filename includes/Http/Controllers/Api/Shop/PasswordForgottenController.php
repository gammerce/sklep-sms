<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\Routing\UrlGenerator;
use App\Support\Mailer;
use App\Theme\Template;
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

        $validator = new Validator(
            [
                "username" => trim($request->request->get("username") ?? ""),
                "email" => trim($request->request->get("email") ?? ""),
            ],
            [
                "username" => [new UsernameRule()],
                "email" => [new EmailRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $editedUser =
            $userRepository->findByEmail($validated["email"]) ?:
            $userRepository->findByUsername($validated["username"]);
        if (!$editedUser) {
            return new ApiResponse("sent", $lang->t("email_sent"), 1);
        }

        $code = $userRepository->createResetPasswordKey($editedUser->getId());

        $text = $template->renderNoComments("emails/forgotten_password", [
            "ip" => $editedUser->getLastip(),
            "link" => $url->to("/page/reset_password", compact("code")),
        ]);
        $ret = $mailer->send(
            $editedUser->getEmail(),
            $editedUser->getUsername(),
            "Reset Hasła",
            $text
        );

        if ($ret === "not_sent") {
            return new ApiResponse("not_sent", $lang->t("keyreset_error"), 0);
        }

        if ($ret === "wrong_email") {
            return new ApiResponse("wrong_sender_email", $lang->t("wrong_email"), 0);
        }

        if ($ret === "sent") {
            $logger->log(
                "log_reset_key_email",
                $editedUser->getUsername(),
                $editedUser->getId(),
                $editedUser->getEmail(),
                $validated["username"],
                $validated["email"]
            );
            return new ApiResponse("sent", $lang->t("email_sent"), 1);
        }

        throw new UnexpectedValueException("Invalid ret value");
    }
}
