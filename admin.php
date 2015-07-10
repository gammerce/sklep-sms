<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "admin");

require_once "global.php";
$G_PID = $G_PID == "login" || $heart->page_exists($G_PID, "admin") ? $G_PID : "home";

// Uzytkownik nie jest zalogowany
if ($G_PID == "login") {
	$heart->page_title = "Login";
	$heart->style_add($settings['shop_url_slash'] . "styles/admin/style_login.css?version=" . VERSION);

	if (isset($_SESSION['info'])) {
		if ($_SESSION['info'] == "wrong_data") {
			$text = $lang->wrong_login_data;
			eval("\$warning = \"" . get_template("admin/login_warning") . "\";");
		} else if ($_SESSION['info'] == "no_privilages") {
			$text = $lang->no_access;
			eval("\$warning = \"" . get_template("admin/login_warning") . "\";");
		}
		unset($_SESSION['info']);
	}

	// Pobranie headera
	eval("\$header = \"" . get_template("admin/header") . "\";");

	$get_data = "";
	// Fromatujemy dane get
	foreach ($_GET as $key => $value)
		$get_data .= (!strlen($get_data) ? '?' : '&') . "{$key}={$value}";

	// Pobranie szablonu logowania
	eval("\$output = \"" . get_template("admin/login") . "\";");

	// Wyświetlenie strony
	output_page($output);
}

$content = get_content("admincontent");

// Pobranie przycisków do sidebaru
if (get_privilages("view_player_flags")) {
	$pid = "players_flags";
	$name = $lang->{$pid};
	eval("\$players_flags_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_player_services")) {
	$pid = "users_services";
	$name = $lang->{$pid};
	eval("\$users_services_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_income")) {
	$pid = "income";
	$name = $lang->{$pid};
	eval("\$income_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("manage_settings")) {
	// Ustawienia sklepu
	$pid = "settings";
	$name = $lang->{$pid};
	eval("\$settings_link = \"" . get_template("admin/page_link") . "\";");

	// Płatności
	$pid = "transaction_services";
	$name = $lang->{$pid};
	eval("\$transaction_services_link = \"" . get_template("admin/page_link") . "\";");

	// Taryfy
	$pid = "tariffs";
	$name = $lang->{$pid};
	eval("\$tariffs_link = \"" . get_template("admin/page_link") . "\";");

	// Cennik
	$pid = "pricelist";
	$name = $lang->{$pid};
	eval("\$pricelist_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_users")) {
	$pid = "users";
	$name = $lang->{$pid};
	eval("\$users_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_groups")) {
	$pid = "groups";
	$name = $lang->{$pid};
	eval("\$groups_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_servers")) {
	$pid = "servers";
	$name = $lang->{$pid};
	eval("\$servers_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_services")) {
	$pid = "services";
	$name = $lang->{$pid};
	eval("\$services_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_sms_codes")) {
	// Kody SMS
	$pid = "sms_codes";
	$name = $lang->{$pid};
	eval("\$sms_codes_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_service_codes")) {
	$pid = "service_codes";
	$name = $lang->{$pid};
	eval("\$service_codes_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_antispam_questions")) {
	// Pytania bezpieczeństwa
	$pid = "antispam_questions";
	$name = $lang->{$pid};
	eval("\$antispam_questions_link = \"" . get_template("admin/page_link") . "\";");
}
if (get_privilages("view_logs")) {
	// Pytania bezpieczeństwa
	$pid = "logs";
	$name = $lang->{$pid};
	eval("\$logs_link = \"" . get_template("admin/page_link") . "\";");
}

// Pobranie headera
eval("\$header = \"" . get_template("admin/header") . "\";");

// Pobranie ostatecznego szablonu
eval("\$output = \"" . get_template("admin/index") . "\";");

// Wyświetlenie strony
output_page($output);