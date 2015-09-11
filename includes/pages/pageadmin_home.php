<?php

$heart->register_page("home", "PageAdminMain", "admin");

class PageAdminMain extends PageAdmin
{

	const PAGE_ID = "home";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('main_page');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $a_Tasks, $templates;

		//
		// Ogloszenia

		$notes = "";

		// Info o braku licki
		if ($a_Tasks['text'] != "logged_in")
			$this->add_note($lang->translate('license_error'), "negative", $notes);

		$a_Tasks['expire_seconds'] = strtotime($a_Tasks['expire']) - time();
		if ($a_Tasks['expire'] != -1 && $a_Tasks['expire_seconds'] >= 0 && $a_Tasks['expire_seconds'] < 4 * 24 * 60 * 60)
			$this->add_note($lang->sprintf($lang->translate('license_soon_expire'), secondsToTime(strtotime($a_Tasks['expire']) - time())), "negative", $notes);

		// Info o katalogu install
		if (file_exists(SCRIPT_ROOT . "install"))
			$this->add_note($lang->translate('remove_install'), "negative", $notes);

		// Sprawdzanie wersji skryptu
		$next_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_next&type=web&version=" . VERSION));
		if (strlen($next_version)) {
			$newest_version = trim(curl_get_contents("http://www.sklep-sms.pl/version.php?action=get_newest&type=web"));
			if (strlen($newest_version) && VERSION != $newest_version)
				$this->add_note($lang->sprintf($lang->translate('update_available'), htmlspecialchars($newest_version)), "positive", $notes);
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
			$this->add_note($lang->sprintf($lang->translate('update_available_servers'), $amount, $heart->get_servers_amount(), htmlspecialchars($newest_version)), "positive", $notes);

		//
		// Cegielki informacyjne

		$bricks = "";

		// Info o serwerach
		$bricks .= create_brick($lang->sprintf($lang->translate('amount_of_servers'), $heart->get_servers_amount()), "brick_pa_main");

		// Info o użytkownikach
		$bricks .= create_brick($lang->sprintf($lang->translate('amount_of_users'), $db->get_column("SELECT COUNT(*) FROM `" . TABLE_PREFIX . "users`", "COUNT(*)")), "brick_pa_main");

		// Info o kupionych usługach
		$amount = $db->get_column(
			"SELECT COUNT(*) " .
			"FROM ({$settings['transactions_query']}) AS t",
			"COUNT(*)"
		);
		$bricks .= create_brick($lang->sprintf($lang->translate('amount_of_bought_services'), $amount), "brick_pa_main");

		// Info o wysłanych smsach
		$amount = $db->get_column(
			"SELECT COUNT(*) AS `amount` " .
			"FROM ({$settings['transactions_query']}) as t " .
			"WHERE t.payment = 'sms' AND t.free='0'",
			"amount"
		);
		$bricks .= create_brick($lang->sprintf($lang->translate('amount_of_sent_smses'), $amount), "brick_pa_main");

		// Pobranie wyglądu strony
		$output = eval($templates->render("admin/home"));
		return $output;
	}

	private function add_note($text, $class, &$notes)
	{
		$notes .= create_dom_element("div", $text, array(
			'class' => "note " . $class
		));
	}

}