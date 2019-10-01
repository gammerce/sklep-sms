<?php
namespace App\Install;

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
        if ($this->installManager->hasFailed()) {
            return $this->installManager->createErrorResponse();
        }

        if (file_exists($this->app->path('_install/progress'))) {
            output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");
        }

        $filesPriv = $filesDel = [];

        if (file_exists($this->app->path('_install/storage/files_privileges.txt'))) {
            $filesPriv = explode(
                "\n",
                str_replace(
                    "\n\r",
                    "\n",
                    file_get_contents($this->app->path('_install/storage/files_privileges.txt'))
                )
            );
        }
        $filesPriv[] = "_install";

        if (file_exists($this->app->path('_install/storage/files_to_delete.txt'))) {
            $filesDel = explode(
                "\n",
                str_replace(
                    "\n\r",
                    "\n",
                    file_get_contents($this->app->path('_install/storage/files_to_delete.txt'))
                )
            );
        }

        // Wymagane moduły
        $modules = [];

        return [$modules, $filesPriv, $filesDel];
    }
}
