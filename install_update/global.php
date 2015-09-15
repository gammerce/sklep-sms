<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Emulujemy wersję PHP
if (!defined('PHP_VERSION_ID')) {
	$version = explode('.', PHP_VERSION);

	define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

// Błąd podczas aktualizacji
const UPDATE_ERROR = "Wystąpił błąd podczas aktualizacji.<br />Poinformuj o swoim problemie na forum sklepu. Do wątku załącz plik errors/update.log";

$working_dir = dirname(dirname(__FILE__));
require_once $working_dir . "/includes/init.php";
require_once SCRIPT_ROOT . "includes/config.php";
require_once SCRIPT_ROOT . "includes/class_template.php";
require_once SCRIPT_ROOT . "includes/functions.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";
require_once SCRIPT_ROOT . "install/includes/functions.php";
require_once SCRIPT_ROOT . "includes/mysqli.php";
require_once SCRIPT_ROOT . "includes/class_translator.php";

set_exception_handler("exceptionHandler");

if (file_exists(SCRIPT_ROOT . "install/error"))
	output_page(UPDATE_ERROR);

if (file_exists(SCRIPT_ROOT . "install/block"))
	output_page("Aktualizacja została już przeprowadzona. Jeżeli chcesz dokonać jej ponownie, usuń plik 'block' z folderu install.");

if (file_exists(SCRIPT_ROOT . "install/progress"))
	output_page("Aktualizacja trwa, lub została błędnie przeprowadzona.");

// Tworzymy obiekt szablonów
$templates = new Templates();

// Tworzymy obiekt języka
$lang = new Translator("polish");

$warnings = $files_priv = $files_del = array();

if (file_exists(SCRIPT_ROOT . "install/files_priv.txt"))
	$files_priv = explode("\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/files_priv.txt")));
$files_priv[] = "install";

if (file_exists(SCRIPT_ROOT . "install/files_del.txt"))
	$files_del = explode("\n", str_replace("\n\r", "\n", file_get_contents(SCRIPT_ROOT . "install/files_del.txt")));

// Wymagane moduły
$modules = array();
