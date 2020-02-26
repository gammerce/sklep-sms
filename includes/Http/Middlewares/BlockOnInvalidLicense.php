<?php
namespace App\Http\Middlewares;

use App\Requesting\Response as CustomResponse;
use App\Routing\UrlGenerator;
use App\Support\Template;
use App\System\License;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Closure;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockOnInvalidLicense implements MiddlewareContract
{
    /** @var License */
    private $license;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    /** @var UrlGenerator */
    private $url;

    public function __construct(
        License $license,
        Template $template,
        TranslationManager $translationManager,
        UrlGenerator $url
    ) {
        $this->license = $license;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->url = $url;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        if (!$this->license->isValid()) {
            $e = $this->license->getLoadingException();
            $message = $this->getMessageFromInvalidResponse($e->response);

            if (starts_with($request->getPathInfo(), "/api")) {
                return new JsonResponse(compact('message'), Response::HTTP_PAYMENT_REQUIRED);
            }

            return $this->renderErrorPage($message);
        }

        return $next($request);
    }

    private function getMessageFromInvalidResponse(CustomResponse $response = null)
    {
        if ($response) {
            if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                return "Nieprawidłowy token licencji.";
            }

            if ($response->getStatusCode() === Response::HTTP_PAYMENT_REQUIRED) {
                return "Przekroczono limit stron WWW korzystających z licencji. Odczekaj 60 minut.";
            }
        }

        return $this->lang->t('verification_error');
    }

    private function renderErrorPage($message)
    {
        return new Response(
            $this->template->render("license/error", [
                'lang' => $this->lang,
                'message' => $message,
                'url' => $this->url,
            ])
        );
    }
}
