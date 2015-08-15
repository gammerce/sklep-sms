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
			$warning = eval($templates->render("admin/login_warning"));
		} else if ($_SESSION['info'] == "no_privilages") {
			$text = $lang->no_access;
			$warning = eval($templates->render("admin/login_warning"));
		}
		unset($_SESSION['info']);
	}

	// Pobranie headera
	$header = eval($templates->render("admin/header"));

	$get_data = "";
	// Fromatujemy dane get
	foreach ($_GET as $key => $value)
		$get_data .= (!strlen($get_data) ? '?' : '&') . "{$key}={$value}";

	// Pobranie szablonu logowania
	$output = eval($templates->render("admin/login"));

	// Wyświetlenie strony
	output_page($output);
}

$content = get_content("admincontent");

// Pobranie przycisków do sidebaru
if (get_privilages("view_player_flags")) {
	$pid = "players_flags";
	$name = $lang->{$pid};
	$players_flags_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_user_services")) {
	$pid = "user_service&subpage=extra_flags";
	$name = $lang->users_services;
	$user_service_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_income")) {
	$pid = "income";
	$name = $lang->{$pid};
	$income_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("manage_settings")) {
	// Ustawienia sklepu
	$pid = "settings";
	$name = $lang->{$pid};
	$settings_link = eval($templates->render("admin/page_link"));

	// Płatności
	$pid = "transaction_services";
	$name = $lang->{$pid};
	$transaction_services_link = eval($templates->render("admin/page_link"));

	// Taryfy
	$pid = "tariffs";
	$name = $lang->{$pid};
	$tariffs_link = eval($templates->render("admin/page_link"));

	// Cennik
	$pid = "pricelist";
	$name = $lang->{$pid};
	$pricelist_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_users")) {
	$pid = "users";
	$name = $lang->{$pid};
	$users_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_groups")) {
	$pid = "groups";
	$name = $lang->{$pid};
	$groups_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_servers")) {
	$pid = "servers";
	$name = $lang->{$pid};
	$servers_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_services")) {
	$pid = "services";
	$name = $lang->{$pid};
	$services_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_sms_codes")) {
	// Kody SMS
	$pid = "sms_codes";
	$name = $lang->{$pid};
	$sms_codes_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_service_codes")) {
	$pid = "service_codes";
	$name = $lang->{$pid};
	$service_codes_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_antispam_questions")) {
	// Pytania bezpieczeństwa
	$pid = "antispam_questions";
	$name = $lang->{$pid};
	$antispam_questions_link = eval($templates->render("admin/page_link"));
}
if (get_privilages("view_logs")) {
	// Pytania bezpieczeństwa
	$pid = "logs";
	$name = $lang->{$pid};
	$logs_link = eval($templates->render("admin/page_link"));
}

// Pobranie headera
$header = eval($templates->render("admin/header"));

// Pobranie ostatecznego szablonu
$output = eval($templates->render("admin/index"));

// Wyświetlenie strony
output_page($output);