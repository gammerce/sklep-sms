<?php
namespace App\Http\Responses;

use App\Http\RequestHelper;
use App\Routing\UrlGenerator;
use App\Services\IntendedUrlService;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Html\Li;
use App\View\Html\Ul;
use App\View\Renders\ErrorRenderer;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseFactory
{
    /** @var ServerResponseFactory */
    private $serverResponseFactory;

    /** @var Translator */
    private $lang;

    /** @var ErrorRenderer */
    private $errorRenderer;

    /** @var IntendedUrlService */
    private $intendedUrlService;

    /** @var UrlGenerator */
    private $url;

    public function __construct(
        ServerResponseFactory $serverResponseFactory,
        ErrorRenderer $errorRenderer,
        IntendedUrlService $intendedUrlService,
        UrlGenerator $url,
        TranslationManager $translationManager
    ) {
        $this->lang = $translationManager->user();
        $this->serverResponseFactory = $serverResponseFactory;
        $this->errorRenderer = $errorRenderer;
        $this->intendedUrlService = $intendedUrlService;
        $this->url = $url;
    }

    public function createWarnings(Request $request, array $data)
    {
        $requestHelper = new RequestHelper($request);

        if ($requestHelper->isFromServer()) {
            $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
            return $this->serverResponseFactory->create(
                $acceptHeader,
                "warnings",
                $this->lang->t("form_wrong_filled"),
                false,
                $data
            );
        }

        if ($requestHelper->acceptsNewFormat($request)) {
            return new JsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new ApiResponse(
            "warnings",
            $this->lang->t("form_wrong_filled"),
            false,
            array_merge($data, [
                "warnings" => $this->formatWarnings($data["warnings"]),
            ])
        );
    }

    public function createError(Request $request, $id, $text, $status = null)
    {
        $requestHelper = new RequestHelper($request);

        if ($requestHelper->isFromServer()) {
            $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
            return $this->serverResponseFactory->create(
                $acceptHeader,
                $id,
                $text,
                false,
                [],
                $status ?: 200
            );
        }

        if ($requestHelper->acceptsNewFormat($request)) {
            return new JsonResponse(
                [
                    "id" => $id,
                    "text" => $text,
                ],
                $status ?: 400
            );
        }

        if ($requestHelper->expectsJson()) {
            return new ApiResponse($id, $text, false, [], $status ?: 200);
        }

        return new HtmlResponse($this->errorRenderer->render("$status", $request), $status);
    }

    public function createUnauthorized(Request $request)
    {
        $requestHelper = new RequestHelper($request);

        if ($requestHelper->isFromServer()) {
            $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
            return $this->serverResponseFactory->create(
                $acceptHeader,
                "no_access",
                $this->lang->t("not_logged_or_no_perm"),
                false,
                []
            );
        }

        if ($requestHelper->acceptsNewFormat($request)) {
            return new JsonResponse([], Response::HTTP_FORBIDDEN);
        }

        if ($requestHelper->expectsJson()) {
            return new ApiResponse("no_access", $this->lang->t("not_logged_or_no_perm"), false);
        }

        $this->intendedUrlService->set($request);

        if ($requestHelper->isAdminSession()) {
            return new RedirectResponse($this->url->to("/admin/login"));
        }

        return new RedirectResponse($this->url->to("/login"));
    }

    // TODO Do it on frontend
    private function formatWarnings(array $warnings)
    {
        $output = [];

        foreach ($warnings as $brick => $warning) {
            if ($warning) {
                $items = collect($warning)
                    ->map(function ($text) {
                        return new Li($text);
                    })
                    ->all();

                $help = new Ul($items);
                $help->addClass("form_warning help is-danger");
                $output[$brick] = $help->toHtml();
            }
        }

        return $output;
    }
}
