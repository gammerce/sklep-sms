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

// Pozyskujemy wszystkie klasy implementujące interface cronjob
$classes = array_filter(
	get_declared_classes(),
	function ($className) {
		return in_array('I_Cronjob', class_implements($className));
	}
);

foreach ($classes as $class) {
	$class()::cronjob_pre();
}

// Usuwamy przestarzałe usługi użytkowników
delete_users_old_services();

// Usuwamy przestarzałe logi
if (intval($settings['delete_logs']) != 0)
	$db->query($db->prepare(
		"DELETE FROM `" . TABLE_PREFIX . "logs` " .
		"WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL '%d' DAY)",
		array($settings['delete_logs'])
	));

// Remove files older than 30 days from data/transfers
$path = SCRIPT_ROOT . "data/transfers";
foreach (scandir($path) as $file) {
	if (filectime($path . $file) < time() - 60*60*24*30) {
		unlink($path . $file);
	}
}
unset($path);

foreach ($classes as $class) {
	$class()::cronjob_post();
}