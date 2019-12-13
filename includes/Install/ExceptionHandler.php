<?php
namespace App\Install;

use App\Exceptions\InvalidConfigException;
use App\Exceptions\SqlQueryException;
use App\Http\Responses\ApiResponse;
use App\System\Application;
use App\System\ExceptionHandlerContract;
use App\System\Path;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if ($e instanceof InvalidConfigException) {
            return new Response($e->getMessage());
        }

        return new ApiResponse(
            'error',
            'Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik data/logs/install.log',
            false
        );
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
