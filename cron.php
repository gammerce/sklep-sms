<?php

if (!defined("IN_SCRIPT")) {
	define('IN_SCRIPT', "1");
	define("SCRIPT_NAME", "cron");

	require_once "global.php";

	// Sprawdzenie random stringu
	if ($_GET['key'] != $settings['random_key'] && $argv[1] != $settings['random_key']) {
		output_page($lang->wrong_cron_key);
	}
}

// Usuwamy przestarzałe usługi graczy
delete_users_old_services();

// Remove files older than 30 days from data/transfers
$path = SCRIPT_ROOT . "data/transfers";
foreach (scandir($path) as $file) {
	if (filectime($path . $file) < time() - 60*60*24*30) {
		unlink($path . $file);
	}
}
unset($path);