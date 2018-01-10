<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/full/includes/global.php";

// #########################################
// ##########    Wyświetl dane    ##########
// #########################################

$files_privilages = '';
foreach ($files_priv as $file) {
	if ($file == "") {
		continue;
	}

	if (is_writable(SCRIPT_ROOT . '/' . $file)) {
		$privilage = "ok";
	} else {
		$privilage = "bad";
	}

	$files_privilages .= eval($templates->install_full_render('file_privilages'));
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

	$server_modules .= eval($templates->install_full_render('module'));
}

// Pobranie ostatecznego szablonu
$output = eval($templates->install_full_render('index'));

// Wyświetlenie strony
output_page($output);
