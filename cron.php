<?php

if (!defined("IN_SCRIPT")) {
	define('IN_SCRIPT', "1");
	define("SCRIPT_NAME", "cron");

	require_once "global.php";

	// Sprawdzenie random stringu
	if ($_GET['key'] != $settings['random_key'] && $argv[1] != $settings['random_key']) {
		output_page($lang['wrong_cron_key']);
	}
}

// Usuwamy przestarzałe usługi graczy
delete_players_old_services();