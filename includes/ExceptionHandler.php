<?php
namespace App;

use App\Exceptions\LicenseException;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\RequireInstallationException;
use App\Exceptions\SqlQueryException;
use Exception;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Application */
    private $app;

    /** @var Translator */
    private $lang;

    protected $dontReport = [RequireInstallationException::class, LicenseException::class];

    public function __construct(Application $app, Translator $lang)
    {
        $this->app = $app;
        $this->lang = $lang;
    }

    public function render(Request $request, Exception $e)
    {
        if ($this->app->isDebug()) {
            $exceptionDetails = $this->getExceptionDetails($e);

            return new JsonResponse($exceptionDetails);
        }

        if ($e instanceof LicenseException) {
            return new Response($this->lang->translate('verification_error'));
        }

        if ($e instanceof LicenseRequestException) {
            return new Response($e->getMessage());
        }

        if ($e instanceof RequireInstallationException) {
            return new RedirectResponse('/install');
        }

        return new Response(
            'Coś się popsuło. Więcej informacji znajdziesz w pliku errors/errors.log'
        );
    }

    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $exceptionDetails = $this->getExceptionDetails($e);

        log_error(json_encode($exceptionDetails, JSON_PRETTY_PRINT));

        if ($this->app->bound(Raven_Client::class)) {
            $this->reportToSentry($e);
        }

        if ($e instanceof SqlQueryException) {
            $this->reportSqlException($e);
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

    protected function reportSqlException(SqlQueryException $e)
    {
        if (strlen($e->getQuery())) {
            log_to_file($this->app->sqlLogPath(), $e->getQuery(false));
        }
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
}
