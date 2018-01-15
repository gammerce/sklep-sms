<?php

use App\Template;
use Install\InstallManager;

require_once SCRIPT_ROOT . "install/src/update/includes/functions.php";

if (file_exists(SCRIPT_ROOT . "install/error")) {
    /** @var InstallManager $installManager */
    $installManager = app()->make(InstallManager::class);
    $installManager->showError();
}

if (file_exists(SCRIPT_ROOT . "install/block")) {
    output_page("Aktualizacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
}

if (file_exists(SCRIPT_ROOT . "install/progress")) {
    output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");
}

// Tworzymy obiekt szablonów
$templates = new Template();

$warnings = $files_priv = $files_del = [];

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
