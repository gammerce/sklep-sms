<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "admin");

require_once "global.php";
require_once SCRIPT_ROOT . "includes/functions_admin_content.php";

// Uzytkownik nie jest zalogowany
if (!is_logged() || !$user['privilages']['acp']) {
	$stylesheets[] = "{$settings['shop_url_slash']}styles/admin/style_login.css?version=" . VERSION;

	if (isset($_SESSION['info'])) {
		if ($_SESSION['info'] == "wrong_data") {
			$text = $lang['wrong_login_data'];
			eval("\$warning = \"" . get_template("admin/login_warning") . "\";");
		} else if ($_SESSION['info'] == "no_privilages") {
			$text = $lang['no_access'];
			eval("\$warning = \"" . get_template("admin/login_warning") . "\";");
		}
		unset($_SESSION['info']);
	}

	// Pobranie headera
	$scripts = array_unique($scripts);
	$stylesheets = array_unique($stylesheets);
	foreach($scripts as $key => $script) $scripts[$key] = "<script type=\"text/javascript\" src=\"{$script}\"></script>";
	foreach($stylesheets as $key => $stylesheet) $stylesheets[$key] = "<link href=\"{$stylesheet}\" rel=\"stylesheet\" />";
	$scripts = implode("\n", $scripts);
	$stylesheets = implode("\n", $stylesheets);
	eval("\$header = \"" . get_template("admin/header") . "\";");

	$get_data = "";
	// Fromatujemy dane get
	foreach ($_GET as $key => $value) {
		$get_data .= ($get_data == "" ? '?' : '&') . "{$key}={$value}";
	}

	// Pobranie szablonu logowania
	eval("\$output = \"" . get_template("admin/login") . "\";");

	// Wyświetlenie strony
	output_page($output);
}

// Dodanie stylów oraz skryptów używanych na danej stronie
switch ($G_PID) {
	case "main_content":
		$stylesheets[] = "{$settings['shop_url_slash']}styles/admin/style_main.css?version=" . VERSION;
	case "users":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/users.js?version=" . VERSION;
		break;
	case "settings":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/settings.js?version=" . VERSION;
		break;
	case "groups":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/groups.js?version=" . VERSION;
		break;
	case "antispam_questions":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/antispam_questions.js?version=" . VERSION;
		break;
	case "transaction_services":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/transaction_services.js?version=" . VERSION;
		break;
	case "services":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/services.js?version=" . VERSION;
		break;
	case "servers":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/servers.js?version=" . VERSION;
		break;
	case "tariffs":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/tariffs.js?version=" . VERSION;
		break;
	case "pricelist":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/pricelist.js?version=" . VERSION;
		break;
	case "sms_codes":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/sms_codes.js?version=" . VERSION;
		break;
	case "logs":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/logs.js?version=" . VERSION;
		break;
	case "players_services":
		$scripts[] = "{$settings['shop_url_slash']}jscripts/admin/players_services.js?version=" . VERSION;
		break;
}

// Sprawdzanie wersji
if ($G_PID == "main_content") {
	// Info o braku licki
	if ($a_Tasks['text'] != "logged_in") {
		add_note($lang['license_error'], "negative");
	}

	$a_Tasks['expire_seconds'] = strtotime($a_Tasks['expire']) - time();
	if ($a_Tasks['expire'] != -1 && $a_Tasks['expire_seconds'] >= 0 && $a_Tasks['expire_seconds'] < 4 * 24 * 60 * 60) {
		add_note(newsprintf($lang['license_soon_expire'], secondsToTime(strtotime($a_Tasks['expire']) - time())), "negative");
	}

	// Info o katalogu install
	if (file_exists(SCRIPT_ROOT . "install")) {
		add_note($lang['remove_install'], "negative");
	}

	// Sprawdzanie wersji skryptu
	$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type=web&version=" . VERSION));
	if (strlen($next_version)) {
		$newest_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=web"));
		if (strlen($newest_version) && VERSION != $newest_version) {
			add_note(newsprintf($lang['update_available'], $newest_version), "positive");
		}
	}

	// Sprawdzanie wersji serwerów
	$amount = 0;
	$newest_versions = json_decode(trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=engines")), true);
	foreach ($heart->get_servers() as $server) {
		$engine = "engine_{$server['type']}";
		if (strlen($newest_versions[$engine]) && $server['version'] != $newest_versions[$engine])
			$amount += 1;
	}

	if ($amount)
		add_note(newsprintf($lang['update_available_servers'], $amount, $heart->get_servers_amount(), $newest_version), "positive");
}

$content = get_content($G_PID);

// Pobranie przycisków do sidebaru
if (get_privilages("view_player_flags")) {
	$pid = "players_flags";
	$name = $lang[$pid];
	eval("\$players_flags_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_player_services")) {
	$pid = "players_services";
	$name = $lang[$pid];
	eval("\$players_services_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_income")) {
	$pid = "income";
	$name = $lang[$pid];
	eval("\$income_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("manage_settings")) {
	// Ustawienia sklepu
	$pid = "settings";
	$name = $lang[$pid];
	eval("\$settings_link = \"" . get_template("admin/page_link") . "\";");

	// Płatności
	$pid = "transaction_services";
	$name = $lang[$pid];
	eval("\$transaction_services_link = \"" . get_template("admin/page_link") . "\";");

	// Taryfy
	$pid = "tariffs";
	$name = $lang[$pid];
	eval("\$tariffs_link = \"" . get_template("admin/page_link") . "\";");

	// Cennik
	$pid = "pricelist";
	$name = $lang[$pid];
	eval("\$pricelist_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_users")) {
	$pid = "users";
	$name = $lang[$pid];
	eval("\$users_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_groups")) {
	$pid = "groups";
	$name = $lang[$pid];
	eval("\$groups_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_servers")) {
	$pid = "servers";
	$name = $lang[$pid];
	eval("\$servers_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_services")) {
	$pid = "services";
	$name = $lang[$pid];
	eval("\$services_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_sms_codes")) {
	// Kody SMS
	$pid = "sms_codes";
	$name = $lang[$pid];
	eval("\$sms_codes_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_antispam_questions")) {
	// Pytania bezpieczeństwa
	$pid = "antispam_questions";
	$name = $lang[$pid];
	eval("\$antispam_questions_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_logs")) {
	// Pytania bezpieczeństwa
	$pid = "logs";
	$name = $lang[$pid];
	eval("\$logs_link = \"" . get_template("admin/page_link") . "\";");
}

// Pobranie headera
$scripts = array_unique($scripts);
$stylesheets = array_unique($stylesheets);
foreach($scripts as $key => $script) $scripts[$key] = "<script type=\"text/javascript\" src=\"{$script}\"></script>";
foreach($stylesheets as $key => $stylesheet) $stylesheets[$key] = "<link href=\"{$stylesheet}\" rel=\"stylesheet\" />";
$scripts = implode("\n", $scripts);
$stylesheets = implode("\n", $stylesheets);

eval("\$header = \"" . get_template("admin/header") . "\";");

// Pobranie ostatecznego szablonu
eval("\$output = \"" . get_template("admin/index") . "\";");

// Wyświetlenie strony
output_page($output);