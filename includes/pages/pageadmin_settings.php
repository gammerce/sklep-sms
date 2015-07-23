<?php

$heart->register_page("settings", "PageAdminSettings", "admin");

class PageAdminSettings extends PageAdmin
{

	const PAGE_ID = "settings";
	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->settings;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $lang_shop, $templates;

		// Pobranie listy serwisów transakcyjnych
		$result = $db->query(
			"SELECT id, name, sms, transfer " .
			"FROM `" . TABLE_PREFIX . "transaction_services`"
		);
		$sms_services = $transfer_services = "";
		while ($row = $db->fetch_array_assoc($result)) {
			if ($row['sms'])
				$sms_services .= create_dom_element("option", $row['name'], array(
					'value' => $row['id'],
					'selected' => $row['id'] == $settings['sms_service'] ? "selected" : ""
				));
			if ($row['transfer'])
				$transfer_services .= create_dom_element("option", $row['name'], array(
					'value' => $row['id'],
					'selected' => $row['id'] == $settings['transfer_service'] ? "selected" : ""
				));
		}
		$cron[$settings['cron_each_visit'] ? "yes" : "no"] = "selected";
		$user_edit_service[$settings['user_edit_service'] ? "yes" : "no"] = "selected";

		// Pobieranie listy dostępnych szablonów
		$dirlist = scandir(SCRIPT_ROOT . "themes");
		$themes_list = "";
		foreach ($dirlist as $dir_name)
			if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "themes/" . $dir_name))
				$themes_list .= create_dom_element("option", $dir_name, array(
					'value' => $dir_name,
					'selected' => $dir_name == $settings['theme'] ? "selected" : ""
				));

		// Pobieranie listy dostępnych języków
		$dirlist = scandir(SCRIPT_ROOT . "includes/languages");
		$languages_list = "";
		foreach ($dirlist as $dir_name)
			if ($dir_name[0] != '.' && is_dir(SCRIPT_ROOT . "includes/languages/{$dir_name}"))
				$languages_list .= create_dom_element("option", $lang->languages[$dir_name], array(
					'value' => $dir_name,
					'selected' => $dir_name == $lang_shop->get_current_language() ? "selected" : ""
				));

		// Pobranie wyglądu strony
		$output = eval($templates->render("admin/settings"));
		return $output;
	}

}