<?php
namespace App\Install;

use App\Application;
use App\ExceptionHandlerContract;
use App\Exceptions\SqlQueryException;
use App\TranslationManager;
use App\Translator;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Translator */
    private $lang;

    /** @var InstallManager */
    private $installManager;

    /** @var Application */
    private $app;

    public function __construct(
        Application $app,
        TranslationManager $translationManager,
        InstallManager $installManager
    ) {
        $this->lang = $translationManager->user();
        $this->installManager = $installManager;
        $this->app = $app;
    }

    public function render(Request $request, Exception $e)
    {
        $message =
            'Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log';
        json_output('error', $message, false);
    }

    public function report(Exception $e)
    {
        if ($e instanceof SqlQueryException) {
            return $this->handleSqlException($e);
        }

        $data = [
            "Message: " . $e->getMessage(),
            "File: " . $e->getFile(),
            "Line: " . $e->getLine(),
            "Stack:\n" . $e->getTraceAsString(),
        ];

        return $this->logError(implode("\n", $data));
    }

    public function handleSqlException(SqlQueryException $e)
    {
        $input = [
            "Message: " . $this->lang->translate('mysqli_' . $e->getMessage()),
            "Error: " . $e->getError(),
            "Query: " . $e->getQuery(false),
        ];

        $this->logError(implode("\n", $input));
    }

    public function logError($message)
    {
        file_put_contents($this->app->path('errors/install.log'), $message);
        file_put_contents($this->app->path('install/error'), '');
        $this->installManager->removeInProgress();
    }
}
