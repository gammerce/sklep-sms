<?php
namespace App\Install;

use App\Application;
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

    public function showError()
    {
        output_page(
            'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log'
        );
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
        file_put_contents($this->app->path('install/progress'), "");
    }

    public function removeInProgress()
    {
        unlink($this->app->path('install/progress'));
    }
}
