<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UniqueUserEmailRule;
use App\Http\Validation\Rules\UserPasswordRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\UserRepository;
use App\Routing\UrlGenerator;
use App\Support\Mailer;
use App\System\Auth;
use App\Theme\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * E-mail change with verification: the new address only becomes active after a
 * confirmation message sent to it is acted upon.
 */
class EmailChangeController
{
    public function change(
        Request $request,
        UserRepository $userRepository,
        Auth $auth,
        Mailer $mailer,
        Template $template,
        UrlGenerator $url,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ): Response {
        $lang = $translationManager->user();
        $user = $auth->user();

        if (is_demo_user($user->getId())) {
            return $this->toProfile($url, "demo_mode");
        }

        // Reject obvious cross-site form posts; legitimate same-origin posts are
        // unaffected (and the request still has to be authenticated).
        if ($this->isCrossSite($request, $url)) {
            return $this->toProfile($url, "email_change_invalid");
        }

        $data = [
            "email" => trim($request->request->get("email") ?? ""),
        ];
        $rules = [
            "email" => [
                new RequiredRule(),
                new EmailRule(),
                new MaxLengthRule(128),
                new UniqueUserEmailRule($user->getId()),
            ],
        ];

        // Changing the recovery address is proof-of-ownership sensitive. Accounts
        // that have a password must re-enter it; passwordless (Steam-created)
        // accounts only have the session, so they cannot be asked for one.
        if ($user->hasPassword()) {
            $data["password"] = $request->request->get("password");
            $rules["password"] = [new RequiredRule(), new UserPasswordRule($user)];
        }

        try {
            $validated = (new Validator($data, $rules))->validateOrFail();
        } catch (ValidationException $e) {
            $status = array_key_exists("password", $e->warnings)
                ? "email_change_wrong_password"
                : "email_change_invalid";

            return $this->toProfile($url, $status);
        }

        $key = get_random_string(32);
        $userRepository->setEmailChange($user->getId(), $validated["email"], $key);

        $text = $template->renderNoComments("emails/confirm_email", [
            "username" => $user->getUsername(),
            "link" => $url->to("/page/confirm_email", ["code" => $key]),
        ]);
        $ret = $mailer->send(
            $validated["email"],
            $user->getUsername(),
            $lang->t("email_change_subject"),
            $text
        );

        if ($ret === "not_sent") {
            return $this->toProfile($url, "email_change_not_sent");
        }

        if ($ret === "wrong_email") {
            return $this->toProfile($url, "wrong_email");
        }

        $logger->logWithUser($user, "log_email_change_email", $validated["email"]);

        return $this->toProfile($url, "email_change_sent");
    }

    public function confirm(
        Request $request,
        UserRepository $userRepository,
        Auth $auth,
        Mailer $mailer,
        Template $template,
        UrlGenerator $url,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ): Response {
        $lang = $translationManager->user();
        $code = (string) $request->request->get("code", "");

        $user = $userRepository->findByEmailChangeKey($code);
        if (!$user) {
            return $this->toLogin($url, "email_change_wrong_token");
        }

        if (is_demo_user($user->getId())) {
            return $this->toLogin($url, "demo_mode");
        }

        $newEmail = $userRepository->getPendingEmail($user->getId());
        if ($newEmail === null || $newEmail === "") {
            return $this->toLogin($url, "email_change_wrong_token");
        }

        $existing = $userRepository->findByEmail($newEmail);
        if ($existing && $existing->getId() !== $user->getId()) {
            return $this->toLogin($url, "email_occupied");
        }

        if (!$userRepository->confirmEmailChange($user->getId())) {
            return $this->toLogin($url, "email_occupied");
        }

        $this->notifyOldAddress($user, $mailer, $template, $lang);

        $logger->log("log_email_changed", $user->getId(), $newEmail);
        $user->setEmail($newEmail);

        $isCurrentUser = $auth->check() && $auth->user()->getId() === $user->getId();

        return $isCurrentUser
            ? $this->toProfile($url, "email_changed")
            : $this->toLogin($url, "email_changed");
    }

    private function notifyOldAddress($user, Mailer $mailer, Template $template, $lang): void
    {
        $oldEmail = (string) $user->getEmail();

        if (!strlen($oldEmail) || is_steam_placeholder_email($oldEmail)) {
            return;
        }

        $text = $template->renderNoComments("emails/email_changed", [
            "username" => $user->getUsername(),
        ]);
        $mailer->send($oldEmail, $user->getUsername(), $lang->t("email_changed_subject"), $text);
    }

    private function isCrossSite(Request $request, UrlGenerator $url): bool
    {
        $source = $request->headers->get("Origin") ?: $request->headers->get("Referer");

        // No source header at all: cannot tell (older/privacy tooling); allow and
        // rely on the authentication requirement.
        if (!$source) {
            return false;
        }

        $sourceHost = parse_url((string) $source, PHP_URL_HOST);

        // Present but unusable (e.g. "Origin: null" from a sandboxed iframe):
        // treat as cross-site rather than failing open.
        if (!is_string($sourceHost) || $sourceHost === "") {
            return true;
        }

        // Accept the configured shop host and the actual request host (apex/www,
        // tenant domains, proxies).
        $expectedHosts = array_filter([
            parse_url((string) $url->getShopUrl(), PHP_URL_HOST) ?: null,
            $request->getHost(),
        ]);

        $sourceHost = strtolower($sourceHost);

        foreach ($expectedHosts as $expectedHost) {
            if ($sourceHost === strtolower($expectedHost)) {
                return false;
            }
        }

        return true;
    }

    private function toProfile(UrlGenerator $url, string $status): Response
    {
        return new RedirectResponse($url->to("/page/profile", ["email_status" => $status]));
    }

    private function toLogin(UrlGenerator $url, string $status): Response
    {
        return new RedirectResponse($url->to("/page/login", ["email_status" => $status]));
    }
}
