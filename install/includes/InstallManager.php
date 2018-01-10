<?php

class InstallManager
{
    /** @var InstallManager */
    private static $instance;

    /** @var Translator */
    private $lang;

    private function __construct(Translator $translator)
    {
        $this->lang = $translator;
    }

    public static function instance()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        global $lang;

        return self::$instance = new InstallManager($lang);
    }

    public function handleException(Exception $e)
    {
        if ($e instanceof SqlQueryException) {
            $this->handleSqlException($e);
        }

        $data = [
            "Message: " . $e->getMessage(),
            "File: " . $e->getFile(),
            "Line: " . $e->getLine(),
            "Stack:\n" . $e->getTraceAsString(),
        ];

        $this->logError(implode("\n", $data));
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
        file_put_contents(SCRIPT_ROOT . 'errors/install.log', $message);
        file_put_contents(SCRIPT_ROOT . 'install/error', '');
        $this->removeInProgress();

        $this->showJsonError();
    }

    public function showJsonError()
    {
        $message = 'Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log';

        json_output('error', $message, false);
    }

    public function showError()
    {
        output_page('Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log');
    }

    public function finish()
    {
        $this->removeInProgress();
    }

    public function start()
    {
        $this->putInProgress();
    }

    private function putInProgress()
    {
        file_put_contents(SCRIPT_ROOT . "install/progress", "");
    }

    private function removeInProgress()
    {
        unlink(SCRIPT_ROOT . "install/progress");
    }
}