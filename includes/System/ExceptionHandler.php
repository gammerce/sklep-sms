<?php
namespace App\System;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\InvalidConfigException;
use App\Exceptions\InvalidServiceModuleException;
use App\Exceptions\LicenseException;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Http\RequestHelper;
use App\Http\Responses\PlainResponse;
use App\Http\Responses\ResponseFactory;
use App\Loggers\FileLogger;
use App\Routing\UrlGenerator;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Exception;
use Sentry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionHandler implements ExceptionHandlerContract
{
    private Application $app;
    private Translator $lang;
    private FileLogger $fileLogger;
    private UrlGenerator $url;
    private ResponseFactory $responseFactory;

    private array $dontReport = [
        EntityNotFoundException::class,
        InvalidConfigException::class,
        LicenseException::class,
        UnauthorizedException::class,
        ForbiddenException::class,
        ValidationException::class,
    ];

    public function __construct(
        Application $app,
        TranslationManager $translationManager,
        FileLogger $logger,
        ResponseFactory $apiResponseFactory,
        UrlGenerator $url
    ) {
        $this->app = $app;
        $this->lang = $translationManager->user();
        $this->fileLogger = $logger;
        $this->url = $url;
        $this->responseFactory = $apiResponseFactory;
    }

    /**
     * @param Request $request
     * @param Exception|Throwable $e
     * @return Response
     */
    public function render(Request $request, $e): Response
    {
        if ($e instanceof EntityNotFoundException) {
            return $this->responseFactory->createError(
                $request,
                "error",
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof UnauthorizedException) {
            return $this->responseFactory->createUnauthorized($request);
        }

        if ($e instanceof ForbiddenException) {
            return $this->renderForbiddenException($request);
        }

        if ($e instanceof InvalidServiceModuleException) {
            return $this->responseFactory->createError(
                $request,
                "wrong_module",
                $this->lang->t("bad_module")
            );
        }

        if ($e instanceof ValidationException) {
            return $this->responseFactory->createWarnings(
                $request,
                array_merge(
                    [
                        "warnings" => $e->warnings,
                    ],
                    $e->data
                )
            );
        }

        if (is_debug()) {
            $exceptionDetails = $this->getExceptionDetails($e);
            return new JsonResponse([
                "return_id" => "stack_trace",
                "stack_trace" => $exceptionDetails,
            ]);
        }

        if ($e instanceof LicenseException) {
            return new PlainResponse($this->lang->t("verification_error"));
        }

        if ($e instanceof LicenseRequestException) {
            return new PlainResponse($e->getMessage());
        }

        if ($e instanceof InvalidConfigException) {
            return new PlainResponse($e->getMessage());
        }

        return $this->responseFactory->createError(
            $request,
            "error",
            $e->getMessage(),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @param Exception|Throwable $e
     * @return void
     */
    public function report($e): void
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $exceptionDetails = $this->getExceptionDetails($e);
        $this->fileLogger->error(json_encode($exceptionDetails, JSON_PRETTY_PRINT));

        report_to_sentry($e);
    }

    /**
     * @param Exception|Throwable $e
     * @return array
     */
    private function getExceptionDetails($e): array
    {
        return [
            "message" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "code" => $e->getCode(),
            "trace" => $e->getTrace(),
        ];
    }

    /**
     * @param Exception|Throwable $e
     * @return bool
     */
    private function shouldReport($e): bool
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    private function renderForbiddenException(Request $request): Response
    {
        $requestHelper = new RequestHelper($request);

        if ($requestHelper->isAdminSession()) {
            return new RedirectResponse($this->url->to("/admin"));
        }

        return new RedirectResponse($this->url->to("/"));
    }
}
