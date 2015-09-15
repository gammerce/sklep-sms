<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Emulujemy wersję PHP
if (!defined('PHP_VERSION_ID')) {
	$version = explode('.', PHP_VERSION);

	define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

// Błąd podczas instalacji
const INSTALL_ERROR = "Wystąpił błąd podczas instalacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/install.log";

$working_dir = dirname(dirname(__FILE__));
require_once $working_dir . "/includes/init.php";
require_once SCRIPT_ROOT . "includes/class_template.php";
require_once SCRIPT_ROOT . "includes/functions.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";
require_once SCRIPT_ROOT . "install/includes/functions.php";
require_once SCRIPT_ROOT . "includes/mysqli.php";
require_once SCRIPT_ROOT . "includes/class_translator.php";

set_exception_handler("exceptionHandler");

if (file_exists(SCRIPT_ROOT . "install/error"))
	output_page(INSTALL_ERROR);

if (file_exists(SCRIPT_ROOT . "install/block"))
	output_page("Instalacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");

if (file_exists(SCRIPT_ROOT . "install/progress"))
	output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");

// Tworzymy obiekt szablonów
$templates = new Templates();

// Tworzymy obiekt języka
$lang = new Translator("polish");

// Którym plikom / folderom trzeba nadać uprawnienia do zapisywania
$files_priv = array();
if (file_exists(SCRIPT_ROOT . "install/files_priv.txt")) {
	$files_priv = explode("\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/files_priv.txt")));
}
$files_priv[] = "install";

// Wymagane moduły
$modules = array(
	array(
		'text' => "PHP v5.3.0 lub wyższa",
		'value' => PHP_VERSION_ID >= 50300,
		'must-be' => false
	),

	array(
		'text' => "Moduł cURL",
		'value' => function_exists('curl_version'),
		'must-be' => true
	)
);
