<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install_index");

require_once "global.php";

// #########################################
// ##########    Wyświetl dane    ##########
// #########################################

$files_privilages = '';
foreach ($files_priv as $file) {
	if ($file == "") continue;

	if (is_writable(SCRIPT_ROOT . '/' . $file))
		$privilage = "ok";
	else
		$privilage = "bad";

	$files_privilages .= eval($templates->install_render('file_privilages'));
}

$server_modules = '';
foreach ($modules as $module) {
	if ($module['value']) {
		$status = "correct";
		$title = "Prawidłowo";
	} else {
		$status = "incorrect";
		$title = "Nieprawidłowo";
	}

	$server_modules .= eval($templates->install_render('module'));
}

// Pobranie ostatecznego szablonu
$output = eval($templates->install_render('index'));

// Wyświetlenie strony
output_page($output);
