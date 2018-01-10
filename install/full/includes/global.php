<?php

// Błąd podczas instalacji
const INSTALL_ERROR = "Wystąpił błąd podczas instalacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log";

require_once SCRIPT_ROOT . "install/full/includes/functions.php";

if (file_exists(SCRIPT_ROOT . "install/error")) {
    output_page(INSTALL_ERROR);
}

if (file_exists(SCRIPT_ROOT . "install/block")) {
    output_page("Instalacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");
}

if (file_exists(SCRIPT_ROOT . "install/progress")) {
    output_page("Instalacja trwa, lub została błędnie przeprowadzona.");
}

// Tworzymy obiekt szablonów
$templates = new Templates();

// Tworzymy obiekt języka
$lang = new Translator("polish");

// Którym plikom / folderom trzeba nadać uprawnienia do zapisywania
$files_priv = [];
if (file_exists(SCRIPT_ROOT . "install/full/storage/files_priv.txt")) {
    $files_priv = explode(
        "\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/full/storage/files_priv.txt"))
    );
}
$files_priv[] = "install";

// Wymagane moduły
$modules = [
    [
        'text'    => "PHP v5.3.0 lub wyższa",
        'value'   => PHP_VERSION_ID >= 50300,
        'must-be' => false,
    ],

    [
        'text'    => "Moduł cURL",
        'value'   => function_exists('curl_version'),
        'must-be' => true,
    ],
];
