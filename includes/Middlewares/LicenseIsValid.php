<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use App\Exceptions\InvalidResponse;
use App\Exceptions\RequestException;
use App\License;
use App\Template;
use App\TranslationManager;
use App\Translator;
use Raven_Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseIsValid implements MiddlewareContract
{
    /** @var Application */
    private $app;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    /** @var Auth */
    private $auth;

    public function __construct(
        Application $app,
        Template $template,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $this->app = $app;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->auth = $auth;
    }

    public function handle(Request $request, Application $app)
    {
        /** @var License $license */
        $license = $app->make(License::class);

        try {
            $license->validate();
        } catch (RequestException $e) {
            return $this->renderErrorPage($this->lang->translate('verification_error'));
        } catch (InvalidResponse $e) {
            $this->limitPrivileges();

            // TODO Move it to index controller
            if (SCRIPT_NAME == "index") {
                $message = $this->getMessageFromInvalidResponse($e->response);
                return $this->renderErrorPage($message);
            }

            // We want to continue because e.g. we want user to be able
            // to change license credentials via admin panel
            return null;
        }

        // Let's pass some additional info to sentry logger
        // so that it would be easier for us to debug any potential exceptions
        if ($this->app->bound(Raven_Client::class)) {
            $this->app->make(Raven_Client::class)->tags_context([
                'license_id' => $license->getExternalId(),
            ]);
        }

        return null;
    }

    private function limitPrivileges()
    {
        $user = $this->auth->user();

        if (get_privilages("manage_settings", $user)) {
            $user->removePrivilages();
            $user->setPrivilages([
                "acp"             => true,
                "manage_settings" => true,
            ]);
        }
    }

    private function getMessageFromInvalidResponse(\App\Requesting\Response $response)
    {
        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            return 'Nieprawidłowy token licencji.';
        }

        if ($response->getStatusCode() === Response::HTTP_PAYMENT_REQUIRED) {
            return 'Przekroczono limit stron WWW korzystających z licencji. Odczekaj 60 minut.';
        }

        return $this->lang->translate('verification_error');
    }

    private function renderErrorPage($message)
    {
        return new Response($this->template->render("license/error", compact('message')));
    }
}
