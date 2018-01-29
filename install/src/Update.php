<?php
namespace Install;

class Update
{
    /** @var InstallManager */
    private $installManager;

    public function __construct(InstallManager $installManager)
    {
        $this->installManager = $installManager;
    }

    public function get()
    {
        if (file_exists(SCRIPT_ROOT . "install/error")) {
            $this->installManager->showError();
        }

        if (file_exists(SCRIPT_ROOT . "install/block")) {
            output_page("Aktualizacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
        }

        if (file_exists(SCRIPT_ROOT . "install/progress")) {
            output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");
        }

        $files_priv = $files_del = [];

        if (file_exists(SCRIPT_ROOT . "install/storage/update/files_priv.txt")) {
            $files_priv = explode("\n",
                str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/storage/update/files_priv.txt")));
        }
        $files_priv[] = "install";

        if (file_exists(SCRIPT_ROOT . "install/storage/update/files_del.txt")) {
            $files_del = explode("\n",
                str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "iinstall/storage/update/files_del.txt")));
        }

        // Wymagane moduły
        $modules = [];

        return [$modules, $files_priv, $files_del];
    }
}
