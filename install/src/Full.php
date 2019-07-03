<?php
namespace Install;

use App\Application;

class Full
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
            output_page(
                "Instalacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install."
            );
        }

        if (file_exists($this->app->path('install/progress'))) {
            output_page("Instalacja trwa, lub została błędnie przeprowadzona.");
        }

        // Którym plikom / folderom trzeba nadać uprawnienia do zapisywania
        $files_priv = [];
        if (file_exists($this->app->path('install/storage/full/files_priv.txt'))) {
            $files_priv = explode(
                "\n",
                str_replace(
                    "\n\r",
                    "\n",
                    file_get_contents($this->app->path('install/storage/full/files_priv.txt'))
                )
            );
        }
        $files_priv[] = "install";

        // Wymagane moduły
        $modules = [
            [
                'text' => "PHP v5.6.0 lub wyższa",
                'value' => PHP_VERSION_ID >= 50600,
                'must-be' => false,
            ],

            [
                'text' => "Moduł cURL",
                'value' => function_exists('curl_version'),
                'must-be' => true,
            ],
        ];

        return [$modules, $files_priv];
    }
}
