<?php
namespace App\Install;

use App\Application;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use App\Translator;

class InstallManager
{
    /** @var Application */
    private $app;

    /** @var Translator */
    private $lang;

    public function __construct(Application $app, Translator $translator)
    {
        $this->app = $app;
        $this->lang = $translator;
    }

    public function createErrorResponse()
    {
        return new HtmlResponse(
            'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku errors/install.log'
        );
    }

    public function start()
    {
        $this->putInProgress();
    }

    public function finish()
    {
        $this->removeInProgress();
    }

    public function markAsFailed()
    {
        file_put_contents($this->app->path('_install/error'), '');
    }

    /** @return bool */
    public function hasFailed()
    {
        return file_exists($this->app->path('_install/error'));
    }

    private function putInProgress()
    {
        file_put_contents($this->app->path('_install/progress'), "");
    }

    public function removeInProgress()
    {
        unlink($this->app->path('_install/progress'));
    }
}
