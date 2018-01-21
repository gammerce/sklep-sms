<?php
namespace App;

use App\Exceptions\LicenseException;
use App\Exceptions\ShopNeedsInstallException;
use Exception;
use SqlQueryException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Translator */
    private $lang;

    protected $dontReport = [
        ShopNeedsInstallException::class,
    ];

    public function __construct(Translator $lang)
    {
        $this->lang = $lang;
    }

    public function render(Request $request, Exception $e)
    {
        if (getenv('APP_DEBUG') === 'true') {
            $exceptionDetails = $this->getExceptionDetails($e);

            return new JsonResponse($exceptionDetails);
        }

        if ($e instanceof LicenseException) {
            return new Response($this->lang->translate('verification_error'));
        }

        if ($e instanceof ShopNeedsInstallException) {
            return new RedirectResponse('/install');
        }

        return new Response('Coś się popsuło. Więcej informacji znajdziesz w pliku errors/errors.log');
    }

    public function report(Exception $e)
    {
        if (!$this->shouldReport($e)) {
            return;
        }

        $exceptionDetails = $this->getExceptionDetails($e);

        log_to_file(ERROR_LOG, json_encode($exceptionDetails, JSON_PRETTY_PRINT));

        if ($e instanceof SqlQueryException) {
            $this->reportSqlException($e);
        }
    }

    protected function getExceptionDetails(Exception $e)
    {
        return [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTrace(),
        ];
    }

    protected function reportSqlException(SqlQueryException $e)
    {
        if (strlen($e->getQuery())) {
            log_to_file(SQL_LOG, $e->getQuery(false));
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
