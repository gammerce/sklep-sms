<?php
namespace App\Middlewares;

use App\Application;
use App\License;
use App\Requesting\Response as CustomResponse;
use App\Template;
use App\TranslationManager;
use App\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockOnInvalidLicense implements MiddlewareContract
{
    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    public function __construct(Template $template, TranslationManager $translationManager)
    {
        $this->template = $template;
        $this->lang = $translationManager->user();
    }

    public function handle(Request $request, Application $app)
    {
        /** @var License $license */
        $license = $app->make(License::class);

        if (!$license->isValid()) {
            $e = $license->getLoadingException();
            $message = $this->getMessageFromInvalidResponse($e->response);

            $jsonScripts = ["jsonhttp.php", "extra_stuff.php", "servers_stuff.php"];
            $executedScript = trim($request->getPathInfo(), "/");

            if (in_array($executedScript, $jsonScripts)) {
                // TODO Check if this works
                return new JsonResponse(compact('message'));
            }

            return $this->renderErrorPage($message);
        }

        return null;
    }

    private function getMessageFromInvalidResponse(CustomResponse $response = null)
    {
        if (!$response) {
            return $this->lang->translate('verification_error');
        }

        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            return "Nieprawidłowy token licencji.";
        }

        if ($response->getStatusCode() === Response::HTTP_PAYMENT_REQUIRED) {
            return "Przekroczono limit stron WWW korzystających z licencji. Odczekaj 60 minut.";
        }

        return $this->lang->translate('verification_error');
    }

    private function renderErrorPage($message)
    {
        return new Response($this->template->render("license/error", compact('message')));
    }
}
