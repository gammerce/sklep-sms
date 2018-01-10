<?php

// Błąd podczas aktualizacji
const UPDATE_ERROR = "Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/update.log";

require_once SCRIPT_ROOT . "install/update/includes/functions.php";

if (file_exists(SCRIPT_ROOT . "install/error")) {
    output_page(UPDATE_ERROR);
}

if (file_exists(SCRIPT_ROOT . "install/block")) {
    output_page("Aktualizacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
}

if (file_exists(SCRIPT_ROOT . "install/progress")) {
    output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");
}

// Tworzymy obiekt szablonów
$templates = new Templates();

// Tworzymy obiekt języka
$lang = new Translator("polish");

$warnings = $files_priv = $files_del = [];

if (file_exists(SCRIPT_ROOT . "install/update/storage/files_priv.txt")) {
    $files_priv = explode("\n",
        str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/update/storage/files_priv.txt")));
}
$files_priv[] = "install";

if (file_exists(SCRIPT_ROOT . "install/update/storage/files_del.txt")) {
    $files_del = explode("\n",
        str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/updates/storage/files_del.txt")));
}

// Wymagane moduły
$modules = [];
