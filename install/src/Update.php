<?php
namespace Install;

use App\Application;

class Update
{
    /** @var Application */
    private $app;

    /** @var InstallManager */
    private $installManager;

    public function __construct(Application $app, InstallManager $installManager)
    {
        $this->app = $app;
        $this->installManager = $installManager;
    }

    public function get()
    {
        if (file_exists($this->app->path('install/error'))) {
            $this->installManager->showError();
        }

        if (file_exists($this->app->path('install/block'))) {
            output_page("Aktualizacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
        }

        if (file_exists($this->app->path('install/progress'))) {
            output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");
        }

        $files_priv = $files_del = [];

        if (file_exists($this->app->path('install/storage/update/files_priv.txt'))) {
            $files_priv = explode(
                "\n",
                str_replace("\n\r", "\n", file_get_contents($this->app->path('install/storage/update/files_priv.txt')))
            );
        }
        $files_priv[] = "install";

        if (file_exists($this->app->path('install/storage/update/files_del.txt'))) {
            $files_del = explode(
                "\n",
                str_replace("\n\r", "\n", file_get_contents($this->app->path('iinstall/storage/update/files_del.txt')))
            );
        }

        // Wymagane moduły
        $modules = [];

        return [$modules, $files_priv, $files_del];
    }
}
