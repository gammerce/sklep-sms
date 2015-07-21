<?php

$heart->register_page("update_web", "PageAdminUpdateWeb", "admin");

class PageAdminUpdateWeb extends PageAdmin
{

	const PAGE_ID = "update_web";
	protected $privilage = "update";

	function __construct()
	{
		global $lang;
		$this->title = $lang->update_web;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $lang, $templates;

		$newest_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=web"));
		$version = simplexml_load_file("http://www.sklep-sms.pl/version.php?action=get_version&type=web&version={$newest_version}", 'SimpleXMLElement', LIBXML_NOCDATA);
		$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type=web&version=" . VERSION));

		// Mamy najnowszą wersję
		if (!strlen($newest_version) || !strlen($next_version) || VERSION == $newest_version) {
			$output = eval($templates->render("admin/no_update"));
			return $output;
		}

		// Pobieramy dodatkowe informacje
		$additional_info = "";
		foreach ($version->extra_info->children() as $value)
			$additional_info .= create_dom_element("li", $value);

		if (strlen($additional_info))
			$additional_info = eval($templates->render("admin/update_additional_info"));

		// Pobieramy listę plików do wymiany
		$files = "";
		foreach ($version->files->children() as $value)
			$files .= create_dom_element("li", $value);

		// Pobieramy listę zmian
		$changelog = "";
		foreach ($version->changelog->children() as $value)
			$changelog .= create_dom_element("li", $value);

		// Pobieramy plik najnowszej wersji full
		$file_data['type'] = "full";
		$file_data['platform'] = "web";
		$file_data['version'] = $newest_version;
		$shop_files['newest_full'] = eval($templates->render("admin/update_file"));

		// Pobieramy plik kolejnej wersji update
		if ($next_version) {
			$file_data['type'] = "update";
			$file_data['platform'] = "web";
			$file_data['version'] = $next_version;
			$shop_files['next_update'] = eval($templates->render("admin/update_file"));
		} else
			$shop_files['next_update'] = $next_version = $lang->none;

		// Pobranie wyglądu całej strony
		$output = eval($templates->render("admin/update_web"));
		return $output;
	}

}