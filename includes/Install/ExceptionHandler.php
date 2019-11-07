<?php
namespace App\Install;

use App\Application;
use App\ExceptionHandlerContract;
use App\Exceptions\SqlQueryException;
use App\Path;
use App\Http\Responses\ApiResponse;
use App\TranslationManager;
use App\Translator;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class ExceptionHandler implements ExceptionHandlerContract
{
    /** @var Translator */
    private $lang;

    /** @var SetupManager */
    private $setupManager;

    /** @var Path */
    private $path;

    public function __construct(
        Application $path,
        TranslationManager $translationManager,
        SetupManager $setupManager
    ) {
        $this->lang = $translationManager->user();
        $this->setupManager = $setupManager;
        $this->path = $path;
    }

    public function render(Request $request, Exception $e)
    {
        $message =
            'Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik data/logs/install.log';
        return new ApiResponse('error', $message, false);
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
        file_put_contents($this->path->to('data/logs/install.log'), $message);
        $this->setupManager->markAsFailed();
        $this->setupManager->removeInProgress();
    }
}
