<?php
namespace App\Http\Responses;

use App\Http\RequestHelper;
use App\Routing\UrlGenerator;
use App\Support\IntendedUrlService;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Renders\ErrorRenderer;
use Exception;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseFactory
{
    private ServerResponseFactory $serverResponseFactory;
    private Translator $lang;
    private ErrorRenderer $errorRenderer;
    private IntendedUrlService $intendedUrlService;
    private UrlGenerator $url;

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

        try {
            $body = $this->errorRenderer->render("$status", $request);
            return new HtmlResponse($body, $status);
        } catch (Exception $e) {
            return new HtmlResponse("$id: $text", $status);
        }
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

    private function formatWarnings(array $warnings)
    {
        return collect($warnings)
            ->mapWithKeys(fn($value) => to_array($value))
            ->all();
    }
}
