<?php

$heart->register_page("update_servers", "PageAdminUpdateServers", "admin");

class PageAdminUpdateServers extends PageAdmin
{
	const PAGE_ID = "update_servers";
	protected $privilage = "update";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('update_servers');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $templates;

		$newest_versions = json_decode(trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=engines")), true);

		$version_bricks = $servers_versions = "";
		foreach ($heart->get_servers() as $server) {
			$engine = "engine_{$server['type']}";
			// Mamy najnowszą wersję
			if ($server['version'] == $newest_versions[$engine]) {
				continue;
			}

			$name = htmlspecialchars($server['name']);
			$current_version = $server['version'];
			$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type={$engine}&version={$server['version']}"));
			$newest_version = $newest_versions[$engine];

			// Nie ma kolejnej wersji
			if (!strlen($next_version)) {
				continue;
			}

			// Pobieramy informacje o danym serwerze, jego obecnej wersji i nastepnej wersji
			$version_bricks .= eval($templates->render("admin/update_version_block"));

			// Pobieramy plik kolejnej wersji update
			$file_data['type'] = "update";
			$file_data['platform'] = $engine;
			$file_data['version'] = $next_version;
			$next_package = eval($templates->render("admin/update_file"));

			// Pobieramy plik najnowszej wersji full
			$file_data['type'] = "full";
			$file_data['platform'] = $engine;
			$file_data['version'] = $newest_version;
			$newest_package = eval($templates->render("admin/update_file"));

			$servers_versions .= eval($templates->render("admin/update_server_version"));
		}

		// Brak aktualizacji
		if (!strlen($version_bricks)) {
			$output = eval($templates->render("admin/no_update"));

			return $output;
		}

		// Pobranie wyglądu całej strony
		$output = eval($templates->render("admin/update_server"));

		return $output;
	}
}