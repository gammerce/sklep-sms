<?php
namespace App\System;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidConfigException;
use App\Exceptions\LicenseException;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Http\RequestHelper;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\HtmlResponse;
use App\Http\Responses\PlainResponse;
use App\Http\Responses\ServerResponseFactory;
use App\Loggers\FileLogger;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Renders\ErrorRenderer;
use Exception;
use Raven_Client;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Application */
    private $app;

    /** @var Translator */
    private $lang;

    /** @var FileLogger */
    private $fileLogger;

    /** @var ErrorRenderer */
    private $errorRenderer;

    /** @var ServerResponseFactory */
    private $serverResponseFactory;

    private $dontReport = [
        EntityNotFoundException::class,
        InvalidConfigException::class,
        LicenseException::class,
        UnauthorizedException::class,
        ValidationException::class,
    ];

    public function __construct(
        Application $app,
        TranslationManager $translationManager,
        FileLogger $logger,
        ErrorRenderer $errorRenderer,
        ServerResponseFactory $serverResponseFactory
    ) {
        $this->app = $app;
        $this->lang = $translationManager->user();
        $this->fileLogger = $logger;
        $this->errorRenderer = $errorRenderer;
        $this->serverResponseFactory = $serverResponseFactory;
    }

    public function render(Request $request, Exception $e)
    {
        if ($e instanceof EntityNotFoundException) {
            return $this->renderError(404, $e, $request);
        }

        if ($e instanceof UnauthorizedException) {
            return new ApiResponse("no_access", $this->lang->t('not_logged_or_no_perm'), false);
        }

        if ($e instanceof ValidationException) {
            return new ApiResponse(
                "warnings",
                $this->lang->t('form_wrong_filled'),
                false,
                array_merge(
                    [
                        "warnings" => format_warnings($e->warnings),
                    ],
                    $e->data
                )
            );
        }

        if (is_debug()) {
            $exceptionDetails = $this->getExceptionDetails($e);
            return new JsonResponse($exceptionDetails);
        }

        if ($e instanceof LicenseException) {
            return new PlainResponse($this->lang->t('verification_error'));
        }

        if ($e instanceof LicenseRequestException) {
            return new PlainResponse($e->getMessage());
        }

        if ($e instanceof InvalidConfigException) {
            return new PlainResponse($e->getMessage());
        }

        return $this->renderError(500, $e, $request);
    }

    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $exceptionDetails = $this->getExceptionDetails($e);
        $this->fileLogger->error(json_encode($exceptionDetails, JSON_PRETTY_PRINT));

        if ($this->app->bound(Raven_Client::class)) {
            $this->reportToSentry($e);
        }
    }

    protected function reportToSentry(Exception $e)
    {
        /** @var Raven_Client $client */
        $client = $this->app->make(Raven_Client::class);
        $client->captureException($e);
    }

    protected function getExceptionDetails(Exception $e)
    {
        return [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'trace' => $e->getTrace(),
        ];
    }

    protected function shouldReport(Exception $e)
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    private function renderError($status, Exception $e, Request $request)
    {
        $requestHelper = new RequestHelper($request);

        if ($requestHelper->isFromServer()) {
            $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
            return $this->serverResponseFactory->create(
                $acceptHeader,
                'error',
                $e->getMessage(),
                false,
                [],
                $status
            );
        }

        if ($requestHelper->expectsJson()) {
            return new ApiResponse('error', $e->getMessage(), false, [], $status);
        }

        $output = $this->errorRenderer->render("$status", $request);
        return new HtmlResponse($output, $status);
    }
}
