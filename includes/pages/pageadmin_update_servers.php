<?php

$heart->register_page("update_servers", "PageAdminUpdateServers", "admin");

class PageAdminUpdateServers extends PageAdmin
{

	protected $privilage = "update";

	function __construct()
	{
		global $lang;
		$this->title = $lang->update_servers;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang;

		$newest_versions = json_decode(trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=engines")), true);

		$version_bricks = "";
		foreach ($heart->get_servers() as $server) {
			$engine = "engine_{$server['type']}";
			// Mamy najnowszą wersję
			if ($server['version'] == $newest_versions[$engine])
				continue;

			$name = htmlspecialchars($server['name']);
			$current_version = $server['version'];
			$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type={$engine}&version={$server['version']}"));
			$newest_version = $newest_versions[$engine];

			// Nie ma kolejnej wersji
			if (!strlen($next_version))
				continue;

			// Pobieramy informacje o danym serwerze, jego obecnej wersji i nastepnej wersji
			eval("\$version_bricks .= \"" . get_template("admin/update_version_block") . "\";");

			// Pobieramy plik kolejnej wersji update
			$file_data['type'] = "update";
			$file_data['platform'] = $engine;
			$file_data['version'] = $next_version;
			eval("\$next_package = \"" . get_template("admin/update_file") . "\";");

			// Pobieramy plik najnowszej wersji full
			$file_data['type'] = "full";
			$file_data['platform'] = $engine;
			$file_data['version'] = $newest_version;
			eval("\$newest_package = \"" . get_template("admin/update_file") . "\";");

			eval("\$servers_versions .= \"" . get_template("admin/update_server_version") . "\";");
		}

		// Brak aktualizacji
		if (!strlen($version_bricks)) {
			eval("\$output = \"" . get_template("admin/no_update") . "\";");
			return $output;
		}

		// Pobranie wyglądu całej strony
		eval("\$output = \"" . get_template("admin/update_server") . "\";");
		return $output;
	}

}